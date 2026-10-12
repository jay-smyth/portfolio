This guide walks through `deployments`, the Terraform project that creates the AWS infrastructure for this site. It starts with what Terraform is and why you'd use it. It then goes through the project file by file, and finishes with every command you need to run it against your own AWS account.

You don't need any previous Terraform experience. You will need an AWS account, a terminal and an hour or so.

## What is Terraform?

[Terraform](https://developer.hashicorp.com/terraform/intro) is an **infrastructure as code** (IaC) tool made by HashiCorp. Instead of clicking through the AWS console to create a bucket, a server or a set of permissions, you describe what you want in text files. Terraform then works out what needs to be created, changed or deleted to make your cloud account match those files.

Because the infrastructure is described in code, you get the same benefits you get from application code:

- **It's repeatable.** The same files create the same infrastructure every time, so a staging environment can be an exact copy of production.
- **It's reviewable.** Infrastructure changes go through version control and pull requests like any other change.
- **It's documented.** The files *are* the documentation, so there's no wondering who changed a setting in the console six months ago.
- **It's reversible.** One command removes everything a project created, which is ideal for experiments and short-lived environments.

Terraform isn't specific to AWS. It talks to hundreds of platforms (Azure, Google Cloud, Cloudflare, GitHub, Kubernetes and more) through plugins called *providers*. This guide only uses the AWS provider.

> **Terraform or OpenTofu?** In 2023 HashiCorp moved Terraform to the Business Source License. The community forked the last open-source release as [OpenTofu](https://opentofu.org/), which is a drop-in replacement for most projects, this one included. Everything in this guide uses the `terraform` command, but `tofu` works the same way.

## How Terraform works

Every Terraform workflow follows the same loop:

1. **Write.** You describe resources in `.tf` files using HCL (HashiCorp Configuration Language).
2. **Plan.** `terraform plan` compares your files with what already exists and shows exactly what it would add, change or destroy. Nothing is touched yet.
3. **Apply.** `terraform apply` shows the plan again, asks for confirmation, then makes the API calls to AWS.

Here is a complete, minimal example that creates a single S3 bucket:

```hcl
terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 6.0"
    }
  }
}

provider "aws" {
  region = "eu-west-2"
}

resource "aws_s3_bucket" "example" {
  bucket = "my-example-bucket-1234"
}
```

Running `terraform init`, then `terraform apply`, creates the bucket. Deleting the `resource` block and applying again deletes it.

## Key concepts

These terms come up throughout the guide.

**Providers** are plugins that know how to talk to a particular platform's API. The [AWS provider](https://registry.terraform.io/providers/hashicorp/aws/latest/docs) turns blocks like `aws_s3_bucket` into AWS API calls. `terraform init` downloads the providers a project needs.

**Resources** are the things Terraform creates and manages, such as a bucket, a server or an IAM role. Each one is declared with a `resource` block that has a type (`aws_s3_bucket`) and a local name (`example`).

**Data sources** look up things that already exist without managing them, for example "the latest Amazon Linux image" or "the default VPC in this account". They're declared with `data` blocks.

**Variables and outputs** are a project's inputs and return values. Variables let you change settings (region, instance size, bucket names) without editing the code. Outputs print useful values after an apply, such as a server's IP address.

**Modules** are reusable groups of resources, much like functions. This project has one module per AWS service and calls them from an environment folder. See [Modules](https://developer.hashicorp.com/terraform/language/modules) in the Terraform docs.

**State** is Terraform's record of what it has created. It maps each `resource` block to a real object in AWS, so Terraform knows that `aws_s3_bucket.example` is the bucket called `my-example-bucket-1234`. State is stored in a `terraform.tfstate` file. Losing it means Terraform forgets what it manages, so this project keeps it in S3 (more on that in [Bootstrapping remote state](/blog/terraform-aws/bootstrapping-state)). Read more in the [state documentation](https://developer.hashicorp.com/terraform/language/state).

## Further reading

The official material is excellent and worth bookmarking:

- [What is Terraform?](https://developer.hashicorp.com/terraform/intro): HashiCorp's own introduction.
- [Get started with AWS](https://developer.hashicorp.com/terraform/tutorials/aws-get-started): the official hands-on tutorial series.
- [Terraform language documentation](https://developer.hashicorp.com/terraform/language): the full reference for HCL, resources, variables and modules.
- [CLI commands](https://developer.hashicorp.com/terraform/cli/commands): what `init`, `plan`, `apply` and the rest do.
- [AWS provider documentation](https://registry.terraform.io/providers/hashicorp/aws/latest/docs): every AWS resource type and its arguments.
- [Terraform best practices](https://www.terraform-best-practices.com/): community conventions for structuring projects.

## Before you start

You'll create real resources in an AWS account, and some of them cost money while they run. Left running around the clock, the `dev` environment costs roughly $20–25 a month, almost all of it the EC2 instance. Stopping the instance when you're not using it brings that right down. The [AWS Pricing Calculator](https://calculator.aws/) gives exact figures, and the last step of this guide shows how to remove everything again.

Ready? Start with a tour of the project.
