The infrastructure is up. This chapter puts it to work: connecting to the server, pushing a Docker image to ECR, running it, using S3, and optionally letting GitHub Actions push images for you.

Run the `terraform output` commands below from `deployments/environments/dev`.

## Connect to the server

Session Manager gives you a shell with no SSH keys and no open ports:

```bash
aws ssm start-session --target "$(terraform output -raw ec2_instance_id)"
```

You'll land in a basic shell as `ssm-user`. Switch to `ec2-user`, which the first-boot script added to the `docker` group:

```bash
sudo su - ec2-user
```

Check the setup script finished:

```bash
docker --version
docker compose version
```

If Docker isn't installed yet, the script is still running or failed. Its log is at `/var/log/cloud-init-output.log`. Type `exit` twice to end the session.

> **"TargetNotConnected"?** The SSM agent on a new instance takes a minute or two to register. Wait and try again. If it never connects, check that the instance has a public IP, which it needs to reach the Systems Manager endpoints.

## Push an image to ECR

From your **own machine**, in a terminal with `AWS_PROFILE` set, get the repository URL and log Docker in to ECR:

```bash
REPO=$(terraform output -json ecr_repository_urls | jq -r '."asa/app"')
aws ecr get-login-password | docker login --username AWS --password-stdin "${REPO%%/*}"
```

`${REPO%%/*}` strips the repository path, leaving the registry address (`123456789012.dkr.ecr.eu-west-2.amazonaws.com`), which is what `docker login` needs. You should see `Login Succeeded`. The login lasts 12 hours.

Then build and push. Replace `path/to/app` with the folder containing your app's `Dockerfile`:

```bash
docker build --platform linux/amd64 -t "$REPO:latest" path/to/app
docker push "$REPO:latest"
```

`--platform linux/amd64` matters if you're on an Apple Silicon Mac. The `t3` instance runs x86_64, and an ARM image won't start on it.

The image now appears in the ECR console. A minute later it shows its vulnerability scan results.

> **Tags in production.** `dev` allows overwriting tags, so pushing `latest` repeatedly is fine. Repositories created with the module's default (immutable tags) reject that. Tag each build with something unique, such as the git commit SHA: `-t "$REPO:$(git rev-parse --short HEAD)"`.

## Run it on the server

Connect again and switch to `ec2-user` as above, then:

```bash
ecr-login
docker pull 123456789012.dkr.ecr.eu-west-2.amazonaws.com/asa/app:latest
docker run -d --name app --restart unless-stopped -p 80:80 \
  123456789012.dkr.ecr.eu-west-2.amazonaws.com/asa/app:latest
```

Use the repository URL from your own outputs. `ecr-login` was installed by the first-boot script. It authenticates using the instance's IAM role, so there are no credentials to copy onto the server.

Now open `http://<ec2_public_ip>` in a browser. This assumes your app listens on port 80 inside the container. Adjust `-p` if it uses a different port, for example `-p 80:8080`.

To deploy a new version later, push a new image, then on the server run `docker pull` again, followed by `docker rm -f app` and the same `docker run`. For multi-container apps, copy a `compose.yaml` to the server and use `docker compose up -d` instead.

## Use the S3 buckets

The instance role can read and write the project's buckets. On the server:

```bash
echo "hello" > test.txt
aws s3 cp test.txt s3://asa-dev-assets-123456789012/test.txt
aws s3 ls s3://asa-dev-assets-123456789012/
```

The AWS CLI comes preinstalled on Amazon Linux 2023 and picks up the role automatically. So does every AWS SDK, which means a Laravel app running in a container on this server can use S3 for file storage without access keys. Install `league/flysystem-aws-s3-v3`, then set:

```dotenv
FILESYSTEM_DISK=s3
AWS_DEFAULT_REGION=eu-west-2
AWS_BUCKET=asa-dev-assets-123456789012
```

Leave `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY` unset, and the SDK falls back to the instance role. See Laravel's [filesystem documentation](https://laravel.com/docs/filesystem#amazon-s3-compatible-filesystems) for more.

Trying to reach any *other* bucket in your account from the server fails with `AccessDenied`. That's least privilege doing its job.

## Push images from GitHub Actions (optional)

Rather than building and pushing by hand, you can have GitHub do it on every push to `main`, without storing any AWS keys in GitHub.

### 1. Enable the OIDC role

In `terraform.tfvars`, turn on `github_oidc` and set `subjects` to your repository:

```hcl
github_oidc = {
  enabled  = true
  subjects = ["repo:your-github-user/your-repo:ref:refs/heads/main"]
}
```

The subject controls exactly which workflows can assume the role. This one allows only runs on the `main` branch of that repository. Use `repo:your-github-user/your-repo:*` to allow any branch, tag or pull request, but be careful with public repositories. If your account already has a GitHub OIDC provider from another project, also add `create_provider = false`.

Apply the change and note the new output:

```bash
terraform plan -out=tfplan
terraform apply tfplan
terraform output -raw github_actions_role_arn
```

### 2. Add a workflow

In the **application** repository, create `.github/workflows/push-image.yml`:

```yaml
name: Push image to ECR

on:
  push:
    branches: [main]

permissions:
  id-token: write   # lets the job request an OIDC token
  contents: read

jobs:
  push:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - uses: aws-actions/configure-aws-credentials@v4
        with:
          role-to-assume: arn:aws:iam::123456789012:role/asa-dev-github-actions
          aws-region: eu-west-2

      - id: ecr
        uses: aws-actions/amazon-ecr-login@v2

      - name: Build and push
        env:
          IMAGE: ${{ steps.ecr.outputs.registry }}/asa/app
        run: |
          docker build -t "$IMAGE:${{ github.sha }}" -t "$IMAGE:latest" .
          docker push --all-tags "$IMAGE"
```

Replace the role ARN with your output. Each push to `main` now produces an image tagged with the commit SHA and `latest`. The official docs for [configure-aws-credentials](https://github.com/aws-actions/configure-aws-credentials) and [amazon-ecr-login](https://github.com/aws-actions/amazon-ecr-login) cover more options.
