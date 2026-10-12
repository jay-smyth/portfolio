This is the main event: creating the S3 buckets, ECR repository, IAM role and EC2 instance with a handful of commands. By the end of this chapter you'll have a running server in your AWS account.

Check before you start:

- `terraform -version` shows 1.10 or later.
- `aws sts get-caller-identity` shows your account, with `AWS_PROFILE` exported in this terminal.
- `environments/dev/backend.tf` has your state bucket name in it.

## 1. Create your settings file

All commands in this chapter run from the `dev` environment folder:

```bash
cd deployments/environments/dev
cp terraform.tfvars.example terraform.tfvars
```

Terraform automatically loads a file called `terraform.tfvars`. It's git-ignored, so you can safely put real values in it. Open it in your editor:

```hcl
aws_region  = "eu-west-2"
project     = "asa"
environment = "dev"

s3_buckets       = ["assets", "backups"]
ecr_repositories = ["app"]

ec2_instance_type = "t3.small"
ec2_key_name      = null # SSM Session Manager works without a key pair
ec2_allocate_eip  = true

ec2_ingress_rules = [
  { port = 80, cidr = "0.0.0.0/0", description = "HTTP" },
  { port = 443, cidr = "0.0.0.0/0", description = "HTTPS" },
  # { port = 22, cidr = "203.0.113.10/32", description = "SSH from my IP" },
]

# Let GitHub Actions push images to ECR without long-lived keys
github_oidc = {
  enabled  = false
  subjects = ["repo:YOUR_GITHUB_USER/YOUR_REPO:ref:refs/heads/main"]
}
```

What each setting does:

| Variable | Effect |
|---|---|
| `aws_region` | Region everything is created in. Match it to `backend.tf` if you changed it there. |
| `project`, `environment` | Form the `asa-dev` prefix on every resource name. |
| `s3_buckets` | One bucket per entry, named `asa-dev-<entry>-<account id>`. |
| `ecr_repositories` | One repository per entry, named `asa/<entry>`. |
| `ec2_instance_type` | Server size. `t3.micro` is cheaper (and free-tier eligible on older accounts); `t3.medium` gives more memory. |
| `ec2_key_name` | Name of an existing EC2 key pair if you want SSH as well as Session Manager. Leave `null` otherwise. |
| `ec2_allocate_eip` | `true` gives the server a fixed public IP. |
| `ec2_ingress_rules` | Inbound firewall rules. To open SSH, uncomment the port 22 line and put your own IP with `/32`. Never use `0.0.0.0/0` for SSH. |
| `github_oidc` | Leave disabled for now. [Using your infrastructure](/blog/terraform-aws/using-your-infrastructure) covers turning it on. |

Anything you leave out falls back to the default in `variables.tf`.

## 2. Initialise

```bash
terraform init
```

This does two things:

- Downloads the AWS provider and records its version in `.terraform.lock.hcl`.
- Connects to the S3 backend and checks the state bucket is reachable.

You should see:

```text
Successfully configured the backend "s3"! Terraform will automatically
use this backend unless the backend configuration changes.
...
Terraform has been successfully initialized!
```

If you get an error about the bucket not existing, check the name in `backend.tf`. The last chapter has a [troubleshooting table](/blog/terraform-aws/changes-and-teardown#troubleshooting) for this and other common errors.

## 3. Validate and format

Two quick checks that cost nothing and catch typos before AWS gets involved:

```bash
terraform fmt -recursive ../..
terraform validate
```

`fmt` rewrites files into the standard style. `validate` checks the syntax and that every reference points at something that exists. It should say `Success! The configuration is valid.`

## 4. Plan

```bash
terraform plan -out=tfplan
```

Terraform reads your files, looks up the default VPC and the latest Amazon Linux image, and compares everything with the (currently empty) state. It then prints every resource it would create. Each line is prefixed with a symbol:

| Symbol | Meaning |
|---|---|
| `+` | Create |
| `~` | Update in place |
| `-/+` | Destroy and recreate (replacement) |
| `-` | Destroy |
| `<=` | Read a data source |

Read through the plan. On a first run, everything should be a `+`. With the example settings it ends with:

```text
Plan: 24 to add, 0 to change, 0 to destroy.
```

That's 12 for the two buckets, 2 for the repository and its lifecycle policy, 4 for IAM, and 6 for the instance, its security group rules and Elastic IP.

`-out=tfplan` saves the exact plan to a file, so the next step applies precisely what you just reviewed, even if something changed in between.

## 5. Apply

```bash
terraform apply tfplan
```

Because you're applying a saved plan, Terraform doesn't ask for confirmation again. (Running a bare `terraform apply` would plan again and prompt you to type `yes`.) Creation takes a minute or two. You'll see each resource as it's created:

```text
module.ecr.aws_ecr_repository.this["asa/app"]: Creating...
module.s3["assets"].aws_s3_bucket.this: Creating...
...
module.ec2.aws_instance.this: Still creating... [10s elapsed]
module.ec2.aws_instance.this: Creation complete after 13s [id=i-0abc123def4567890]
...
Apply complete! Resources: 24 added, 0 changed, 0 destroyed.
```

The state is now saved in your S3 bucket under `dev/terraform.tfstate`.

## 6. Read the outputs

Terraform prints the outputs at the end of the apply. You can show them again at any time with:

```bash
terraform output
```

```text
ec2_instance_id = "i-0abc123def4567890"
ec2_public_ip = "18.130.0.10"
ecr_repository_urls = {
  "asa/app" = "123456789012.dkr.ecr.eu-west-2.amazonaws.com/asa/app"
}
s3_buckets = {
  "assets" = "asa-dev-assets-123456789012"
  "backups" = "asa-dev-backups-123456789012"
}
```

For use in scripts, `-raw` prints a single value without quotes, and `-json` prints everything as JSON:

```bash
terraform output -raw ec2_public_ip
terraform output -json ecr_repository_urls | jq -r '."asa/app"'
```

## Check it in the console

It's reassuring to see what Terraform built. In the [AWS console](https://console.aws.amazon.com/), make sure the region selector in the top right matches your `aws_region`, then look at:

- **EC2 → Instances**, where `asa-dev-app` should be *Running*.
- **S3**, which should list your buckets and the state bucket.
- **ECR → Private repositories**, which should show `asa/app`.
- **IAM → Roles**, which should list `asa-dev-ec2`.

Every resource has the tags `Project = asa`, `Environment = dev` and `ManagedBy = terraform`.

> **Give it a few minutes.** The instance is *running* almost immediately, but the first-boot script that installs Docker takes another two to three minutes. Wait for that before connecting in the next step.
