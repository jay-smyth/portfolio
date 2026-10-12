The project uses four AWS services. This chapter explains what each one is, what Terraform creates in it and why it's configured the way it is. If you already know AWS well, the summary table is enough and you can skip ahead.

## At a glance

With the default settings, the `dev` environment creates:

| Service | Resource | Name |
|---|---|---|
| S3 | A bucket for each entry in `s3_buckets` | `asa-dev-assets-<account id>` |
| ECR | A repository for each entry in `ecr_repositories` | `asa/app` |
| IAM | A role, policy and instance profile for the server | `asa-dev-ec2` |
| IAM | *(optional)* A role for GitHub Actions | `asa-dev-github-actions` |
| EC2 | One instance with a security group | `asa-dev-app` |

The `bootstrap` stack adds one more S3 bucket for Terraform state, `asa-tfstate-<account id>`.

Everything is created in one region, **eu-west-2 (London)** by default. You can change this with the `aws_region` variable.

## S3: object storage

[Amazon S3](https://docs.aws.amazon.com/AmazonS3/latest/userguide/Welcome.html) (Simple Storage Service) stores files, called *objects*, in containers called *buckets*. It's typically used for user uploads, backups, static assets and, here, Terraform state.

The `s3` module creates each bucket with security settings that most tutorials leave out:

- **Block Public Access** is fully on, so nothing in the bucket can be made public by accident, even through a bucket policy. ([Docs](https://docs.aws.amazon.com/AmazonS3/latest/userguide/access-control-block-public-access.html))
- **Object ownership** is set to *bucket owner enforced*, which disables old-style ACLs. Permissions are controlled only through IAM.
- **Encryption** is on by default with SSE-S3 (AES-256). You can pass a KMS key instead.
- **Versioning** is on, so overwritten or deleted files can be recovered. A lifecycle rule deletes old versions after 90 days (180 for the state bucket) so storage doesn't grow forever, and cleans up abandoned multipart uploads after 7 days.

In `dev`, `force_destroy` is on so that `terraform destroy` can delete buckets that still contain files. Leave it off in production.

## ECR: a private Docker registry

[Amazon ECR](https://docs.aws.amazon.com/AmazonECR/latest/userguide/what-is-ecr.html) (Elastic Container Registry) is AWS's version of Docker Hub, private to your account. You build the app into a Docker image, push it to ECR, and the server pulls it from there.

The `ecr` module configures each repository with:

- **Scan on push.** Every image is checked for known vulnerabilities, and you can see the results in the console. ([Docs](https://docs.aws.amazon.com/AmazonECR/latest/userguide/image-scanning.html))
- **Tag mutability.** The module defaults to *immutable* tags, so `v1.2.0` can never be overwritten with different code. `dev` overrides this to *mutable* so you can keep pushing `latest` while experimenting.
- **A lifecycle policy.** Untagged images are deleted after 7 days and only the 30 most recent images are kept, so storage costs stay flat. ([Docs](https://docs.aws.amazon.com/AmazonECR/latest/userguide/LifecyclePolicies.html))

Repository names are prefixed with the project, so `app` becomes `asa/app`.

## IAM: who can do what

[AWS IAM](https://docs.aws.amazon.com/IAM/latest/UserGuide/introduction.html) (Identity and Access Management) controls who and what can call AWS APIs. Getting it right is the most important part of any AWS setup, and it's where hand-built infrastructure most often goes wrong.

### The EC2 role

The application server needs to pull images from ECR and read and write files in S3. The insecure way to do that is to copy access keys onto the server. The right way is an **IAM role** attached to the instance through an **instance profile**. The server then gets short-lived, automatically rotated credentials, and there are no keys to leak. ([Docs](https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles_use_switch-role-ec2.html))

The `iam` module creates the role `asa-dev-ec2` with:

| Permission | Allows | Scope |
|---|---|---|
| `AmazonSSMManagedInstanceCore` (AWS managed) | Connecting to the server through Session Manager, with no SSH keys or open port 22 | The instance itself |
| `ecr:GetAuthorizationToken` | Logging Docker in to ECR | Account-wide (AWS requires this) |
| `ecr:BatchGetImage` and related | Pulling images | **Only** the project's repositories |
| `s3:ListBucket` | Listing files | **Only** the project's buckets |
| `s3:GetObject`, `PutObject`, `DeleteObject` | Reading and writing files | **Only** objects in the project's buckets |

This is the *principle of least privilege*: the server can do its job and nothing more. If it were ever compromised, the attacker couldn't touch anything else in your account.

### The GitHub Actions role (optional)

If your code lives on GitHub, CI can build and push images to ECR on every merge. The traditional way is to store AWS access keys as GitHub secrets. A better way is **OpenID Connect (OIDC)**: GitHub issues a signed token for each workflow run, and AWS trusts tokens from your repository and nothing else. No long-lived keys exist anywhere. ([GitHub docs](https://docs.github.com/en/actions/security-for-github-actions/security-hardening-your-deployments/configuring-openid-connect-in-amazon-web-services))

When you enable `github_oidc`, the module creates:

- The GitHub OIDC identity provider in your account. Skip this with `create_provider = false` if one already exists, because an account can only have one.
- A role, `asa-dev-github-actions`, that can only be assumed by workflows matching the `subjects` you list, for example only the `main` branch of one repository.
- A policy allowing that role to push images to the project's ECR repositories, and nothing else.

## EC2: the application server

[Amazon EC2](https://docs.aws.amazon.com/ec2/) (Elastic Compute Cloud) provides virtual servers, called *instances*. This project runs one instance that pulls the app's Docker image from ECR and runs it.

The `ec2` module creates:

- **An instance** running the latest [Amazon Linux 2023](https://docs.aws.amazon.com/linux/al2023/ug/what-is-amazon-linux.html) image (`t3.small` by default). The AMI is looked up at plan time, and `ignore_changes = [ami]` stops Terraform from replacing your server every time Amazon publishes a new image.
- **A security group**, a virtual firewall. By default it allows inbound HTTP (80) and HTTPS (443) from anywhere and all outbound traffic. SSH (22) is closed, because Session Manager makes it unnecessary.
- **An encrypted gp3 root volume** of 20 GiB.
- **IMDSv2 enforced.** The instance metadata service, which is how the server fetches its role credentials, requires session tokens. This blocks a well-known class of server-side request forgery attacks. ([Docs](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/configuring-instance-metadata-service.html))
- **An Elastic IP** (optional). By default an instance gets a new public IP every time it's stopped and started. An Elastic IP stays the same, which is what you want if a DNS record points at it.

### First-boot setup

The instance is passed a *user data* script, `templates/user_data.sh.tftpl`, which runs once on first boot. It:

1. Updates the system and installs Docker.
2. Adds `ec2-user` to the `docker` group.
3. Installs the Docker Compose v2 plugin.
4. Creates an `ecr-login` command that logs Docker in to your ECR registry using the instance role.

Terraform fills in the region and registry address when it renders the template, so the script always matches the environment it's deployed to.

### Networking

To keep things simple, `dev` launches the instance into your account's **default VPC**, the network AWS creates automatically in every region. It picks the first default subnet. That's fine for development. A production setup would normally have its own VPC with private subnets and a load balancer in front. ([Default VPC docs](https://docs.aws.amazon.com/vpc/latest/userguide/default-vpc.html))

If you've deleted the default VPC in your chosen region, the plan will fail at the `data "aws_vpc" "default"` lookup. You can recreate it from the VPC console under **Actions → Create default VPC**.

## Cost

Approximate monthly costs for `dev` in eu-west-2, before any free tier:

| Item | Rough cost |
|---|---|
| `t3.small` instance, running 24/7 | ~$17 |
| 20 GiB gp3 volume | ~$2 |
| Elastic IP / public IPv4 address | ~$3.60 |
| S3 and ECR storage at small volumes | Pennies |

Stopping the instance when you're not using it removes most of the compute cost. Set up an [AWS Budget](https://docs.aws.amazon.com/cost-management/latest/userguide/budgets-managing-costs.html) alert before you start, so a forgotten resource never comes as a surprise.
