`deployments` is a stand-alone Terraform project that lives next to the application code, not inside it. Its job is to create everything the app needs to run on AWS: storage for files, a registry for Docker images, a server to run them on, and the permissions that tie it all together.

Keeping infrastructure in its own directory means it has its own lifecycle. You can rebuild the app a hundred times a day without touching the infrastructure, and change the infrastructure without redeploying the app.

## The layout

```text
deployments/
├── .gitignore
├── README.md
├── bootstrap/
│   └── main.tf
├── modules/
│   ├── s3/       main.tf  variables.tf  outputs.tf
│   ├── ecr/      main.tf  variables.tf  outputs.tf
│   ├── iam/      main.tf  variables.tf  outputs.tf
│   └── ec2/      main.tf  variables.tf  outputs.tf
└── environments/
    └── dev/
        ├── backend.tf
        ├── versions.tf
        ├── main.tf
        ├── variables.tf
        ├── outputs.tf
        ├── terraform.tfvars.example
        └── templates/
            └── user_data.sh.tftpl
```

It has three parts, each with a distinct job.

## bootstrap/: where state lives

Terraform needs somewhere to keep its state file. Storing it on your laptop works for a solo experiment, but it's lost if the laptop is, and nobody else can run Terraform against the same infrastructure.

The `bootstrap` stack solves this. It creates a single, private, versioned S3 bucket that every environment uses to store its state. It's named `asa-tfstate-<your account id>` by default.

This is a chicken-and-egg problem: the bucket that stores state can't store its own state before it exists. So `bootstrap` deliberately uses **local** state, and you run it once per AWS account. It uses the same `s3` module as everything else, so the state bucket gets the same security settings as any other bucket.

## modules/: reusable building blocks

Each module wraps one AWS service with sensible, secure defaults. They don't know about each other or about any particular environment. They take inputs (`variables.tf`), create resources (`main.tf`) and return values (`outputs.tf`).

| Module | What it creates | Key defaults |
|---|---|---|
| `s3` | One S3 bucket | Public access blocked, encryption on, versioning on, old versions deleted after 90 days |
| `ecr` | One or more ECR repositories | Scan on push, immutable tags, keep the last 30 images, untagged images deleted after 7 days |
| `iam` | An EC2 role and instance profile; optionally a GitHub Actions role | Least-privilege access to only the buckets and repositories passed in |
| `ec2` | An EC2 instance, its security group, optionally an Elastic IP | Latest Amazon Linux 2023, IMDSv2 required, encrypted gp3 disk |

The next chapter covers what each of these AWS services actually is.

## environments/dev/: putting it together

An *environment* is a folder that calls the modules with real values and has its own state file. `dev` is the only one for now. A `prod` environment would be a copy of this folder with different settings.

Each file has one job:

| File | Purpose |
|---|---|
| `versions.tf` | Pins Terraform (`>= 1.10`) and the AWS provider (`~> 6.0`), and configures the provider with a region and default tags |
| `backend.tf` | Tells Terraform to store this environment's state in the bootstrap bucket under `dev/terraform.tfstate` |
| `main.tf` | Looks up the default VPC and calls the four modules |
| `variables.tf` | Every setting you can change, each with a default |
| `outputs.tf` | Values printed after an apply: bucket names, repository URLs, the instance ID and IP |
| `terraform.tfvars.example` | A template for your own settings file |
| `templates/user_data.sh.tftpl` | A script the EC2 instance runs on first boot to install Docker |

### How the modules connect

The order in `main.tf` matters less than you might expect, because Terraform works out dependencies from references. When one module uses another module's output, Terraform knows to create the first one before the second:

```hcl
module "iam" {
  source = "../../modules/iam"

  name_prefix         = local.name_prefix
  ecr_repository_arns = values(module.ecr.repository_arns)
  s3_bucket_arns      = [for bucket in module.s3 : bucket.bucket_arn]
  github_oidc         = var.github_oidc
}

module "ec2" {
  source = "../../modules/ec2"

  instance_profile_name = module.iam.ec2_instance_profile_name
  # ...
}
```

This gives a natural build order:

1. **S3** and **ECR** are created first, because they depend on nothing.
2. **IAM** is created next. Its policies need the ARNs (Amazon Resource Names) of the buckets and repositories, so the server gets access to *exactly* those and nothing else.
3. **EC2** is created last, because it needs the instance profile from IAM.

### Naming and tagging

Every resource name starts with `<project>-<environment>`, which is `asa-dev` by default. S3 bucket names must be unique across *all* AWS accounts worldwide, so they also end with your 12-digit account ID, for example `asa-dev-assets-123456789012`.

`versions.tf` sets `default_tags` on the provider, so every resource is tagged automatically with `Project`, `Environment` and `ManagedBy = terraform`. This makes it easy to find everything in the AWS console and to see costs per project in AWS Cost Explorer.

## What isn't committed

The `.gitignore` keeps three kinds of file out of version control:

- **`.terraform/`** holds downloaded providers. `terraform init` recreates it.
- **`*.tfstate`** files. State can contain sensitive values and belongs in the S3 backend, not in git.
- **`*.tfvars`** files, which hold your real settings. Only `terraform.tfvars.example` is committed. You copy it and fill it in.

Do commit `.terraform.lock.hcl` once `terraform init` creates it. It records the exact provider versions used, so everyone gets the same ones.
