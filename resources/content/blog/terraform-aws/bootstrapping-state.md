Before deploying the environment, you need somewhere to keep its Terraform state. This is a one-off step per AWS account. It takes about two minutes.

## Why remote state?

By default, Terraform writes state to a `terraform.tfstate` file in the folder you run it from. That causes problems:

- If the file is lost, Terraform no longer knows what it created. Your resources keep running (and costing money), but Terraform can't update or destroy them.
- Anyone else, or your CI pipeline, can't safely run Terraform against the same infrastructure.
- Two people running `apply` at the same time can corrupt it.

Storing state in S3 solves all three. The bucket is versioned, so earlier state can be recovered. It can be shared with anyone who has access. And Terraform puts a lock file next to the state while it runs, so two applies can't happen at once. Read more about the [S3 backend](https://developer.hashicorp.com/terraform/language/backend/s3).

## 1. Create the state bucket

Make sure your credentials are active (see the [previous chapter](/blog/terraform-aws/connecting-to-aws)), then:

```bash
export AWS_PROFILE=asa
cd deployments/bootstrap
terraform init
```

`init` downloads the AWS provider into a `.terraform/` folder and writes `.terraform.lock.hcl`. You'll see `Terraform has been successfully initialized!`.

Now preview what will be created:

```bash
terraform plan
```

The plan shows the bucket and its supporting resources (ownership controls, public access block, versioning, encryption and lifecycle rule), ending with:

```text
Plan: 6 to add, 0 to change, 0 to destroy.
```

If that looks right, apply it:

```bash
terraform apply
```

Terraform prints the plan again and asks you to type `yes`. When it finishes, it prints the bucket name:

```text
Apply complete! Resources: 6 added, 0 changed, 0 destroyed.

Outputs:

state_bucket_name = "asa-tfstate-123456789012"
```

Copy that name.

> **Using a different region or name?** Pass variables on the command line, for example `terraform apply -var="aws_region=us-east-1"` or `-var="state_bucket_name=my-unique-name"`. Use the same `-var` flags on every later `plan`, `apply` or `destroy` in this folder.

## 2. Keep the bootstrap state safe

`bootstrap` uses **local** state, so there's now a `terraform.tfstate` file in `deployments/bootstrap/`. It's git-ignored, and it's only needed if you ever want Terraform to change or delete the state bucket. Either:

- keep it somewhere safe, such as a password manager or a private backup, or
- don't worry about it. If it's lost, the bucket is easy to manage or delete by hand in the S3 console.

## 3. Point the environment at the bucket

Open `deployments/environments/dev/backend.tf`:

```hcl
terraform {
  backend "s3" {
    bucket       = "REPLACE_WITH_STATE_BUCKET"
    key          = "dev/terraform.tfstate"
    region       = "eu-west-2"
    encrypt      = true
    use_lockfile = true
  }
}
```

Replace `REPLACE_WITH_STATE_BUCKET` with the bucket name from the output. If you created the bucket in another region, change `region` too.

The other settings:

- **`key`** is the path of the state file inside the bucket. Each environment gets its own key, so `dev` and a future `prod` never share state.
- **`encrypt`** encrypts the state file at rest.
- **`use_lockfile`** turns on S3-native locking. Older guides tell you to create a DynamoDB table for locking, but since Terraform 1.10 that's no longer needed.

> **Why not a variable?** Backend blocks are read before variables are evaluated, so they can't use `var.` references. If you'd rather not edit the file, leave the placeholder and supply the value when initialising instead: `terraform init -backend-config="bucket=asa-tfstate-123456789012"`. This is handy in CI, where the bucket name can come from an environment variable.

The groundwork is done. Next, deploy the environment.
