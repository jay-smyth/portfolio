Infrastructure isn't something you create once and forget. This last chapter covers changing what you've built, adding a production environment, fixing common errors and, when you're done, removing everything so it stops costing money.

## Making changes

Every change follows the same loop as the first deploy: **edit, plan, review, apply**.

Say you want a bigger server. Change one line in `terraform.tfvars`:

```hcl
ec2_instance_type = "t3.medium"
```

Then:

```bash
terraform plan -out=tfplan
```

```text
  # module.ec2.aws_instance.this will be updated in-place
  ~ resource "aws_instance" "this" {
        id            = "i-0abc123def4567890"
      ~ instance_type = "t3.small" -> "t3.medium"
        # (unchanged attributes hidden)
    }

Plan: 0 to add, 1 to change, 0 to destroy.
```

`~` means *update in place*. AWS stops the instance, resizes it and starts it again, and the disk is kept. Apply it with `terraform apply tfplan`.

**Always read the plan before applying**, and look out for `-/+` (replace) and `-` (destroy). Some changes can't be made in place. For example, editing `templates/user_data.sh.tftpl` **replaces the instance**, because the module sets `user_data_replace_on_change = true` so the new script actually runs. Anything stored only on the old instance's disk is lost. The plan always tells you, with a `# forces replacement` comment on the attribute responsible.

A few other everyday changes:

| To... | Change |
|---|---|
| Add a bucket | Add a name to `s3_buckets` |
| Add a repository | Add a name to `ecr_repositories` |
| Open SSH to your IP | Add `{ port = 22, cidr = "<your ip>/32" }` to `ec2_ingress_rules` |
| Give the server a fixed IP | Set `ec2_allocate_eip = true` |

New buckets and repositories are automatically added to the EC2 role's permissions, because the IAM module receives their ARNs.

### Drift

If someone changes a Terraform-managed resource by hand in the console, the next `plan` notices and proposes changing it back. That's called *drift*. To see what's drifted without proposing any changes, run:

```bash
terraform plan -refresh-only
```

Either put the change into code or let Terraform revert it. Avoid console edits to anything tagged `ManagedBy = terraform`.

### Saving money between sessions

Stopping the server keeps everything else in place and removes most of the cost:

```bash
aws ec2 stop-instances --instance-ids "$(terraform output -raw ec2_instance_id)"
aws ec2 start-instances --instance-ids "$(terraform output -raw ec2_instance_id)"
```

Terraform doesn't mind, because it doesn't track whether an instance is running. Without an Elastic IP, the public IP changes after a stop and start. Run `terraform apply -refresh-only` to update the `ec2_public_ip` output.

## Adding a production environment

Environments are just folders, so `prod` starts as a copy of `dev`:

```bash
cd deployments/environments
cp -r dev prod
rm -rf prod/.terraform prod/terraform.tfstate* prod/tfplan
```

Then, in `prod`:

1. **`backend.tf`:** change `key` to `"prod/terraform.tfstate"`. This is essential. If two environments share a key, each will try to delete the other's resources.
2. **`terraform.tfvars`:** set `environment = "prod"`, choose an appropriate instance size, and set `ecr_image_tag_mutability = "IMMUTABLE"`.
3. **Run** `terraform init`, then plan and apply as before.

Resources are named `asa-prod-*`, so nothing clashes. `force_destroy` on buckets and `force_delete` on repositories are automatically off, because `main.tf` only enables them when `environment == "dev"`. Production data can't be wiped by an accidental destroy.

For a serious production setup, consider adding a dedicated VPC with private subnets, a load balancer with an HTTPS certificate from ACM, and running Terraform from CI instead of a laptop.

## Tearing down

When you're finished, remove everything so it stops costing money. **Order matters:** destroy the environments first and the state bucket last, because the environments' state lives in that bucket.

### 1. Destroy the environment

```bash
cd deployments/environments/dev
terraform destroy
```

Terraform lists everything it will delete. Type `yes` to confirm. In `dev`, buckets and repositories are deleted even if they still contain files and images.

```text
Destroy complete! Resources: 24 destroyed.
```

Repeat for any other environments, such as `prod`. There, you'll first need to empty its buckets and repositories yourself, because force delete is off.

### 2. Destroy the state bucket (optional)

If you plan to deploy again later, keep the state bucket. It costs next to nothing. To remove it, note that it's versioned and *not* force-destroyable, so it still holds old versions of the state files. Empty it first: in the S3 console, select the bucket and choose **Empty**. That deletes every object version. Then run:

```bash
cd deployments/bootstrap
terraform destroy
```

If you've lost the bootstrap state file, simply delete the bucket in the console after emptying it.

Finally, check the [Billing console](https://console.aws.amazon.com/billing/) over the next day or two to confirm charges have stopped.

## Troubleshooting

| Error | Cause and fix |
|---|---|
| `No valid credential sources found` | Terraform can't find credentials. Run `export AWS_PROFILE=asa` in this terminal and check `aws sts get-caller-identity`. |
| `The SSO session associated with this profile has expired` / `ExpiredToken` | Identity Center credentials expire after a few hours. Run `aws sso login --profile asa`. |
| `S3 bucket "REPLACE_WITH_STATE_BUCKET" does not exist` | `backend.tf` still has the placeholder. See [Bootstrapping remote state](/blog/terraform-aws/bootstrapping-state). |
| `Backend configuration changed` | You edited `backend.tf` after `init`. Run `terraform init -reconfigure`, or `terraform init -migrate-state` if you want to move existing state to the new location. |
| `Error acquiring the state lock` | Another plan or apply is running, or a crashed run left its lock behind. If you're certain nothing else is running, use `terraform force-unlock <LOCK_ID>` with the ID from the error. |
| `BucketAlreadyExists` | S3 bucket names are global and someone else has that name. Pick a different `project` or `state_bucket_name`. |
| `EntityAlreadyExists: Provider with url https://token.actions.githubusercontent.com already exists` | The account already has the GitHub OIDC provider. Add `create_provider = false` to `github_oidc`. |
| `no matching EC2 VPC found` | The default VPC was deleted in this region. Recreate it from the VPC console under **Actions → Create default VPC**. |
| `TargetNotConnected` from Session Manager | The instance is still booting. Wait a couple of minutes. |
| `no basic auth credentials` on `docker push` or `pull` | The ECR login expired (it lasts 12 hours). Log in again, or run `ecr-login` on the server. |
| `tag invalid: ... cannot be overwritten because the repository is immutable` | The repository uses immutable tags. Push a unique tag, such as the commit SHA. |

## Wrapping up

You've gone from an empty AWS account to a server running your app, with private storage, a container registry and properly scoped permissions, all described in code you can review, repeat and remove with one command.

Good next steps:

- **Run Terraform from CI**, so changes are planned on pull requests and applied on merge. HashiCorp's [automating Terraform with GitHub Actions](https://developer.hashicorp.com/terraform/tutorials/automation/github-actions) tutorial is a good start.
- **Add a dedicated VPC**, for example with the community [VPC module](https://registry.terraform.io/modules/terraform-aws-modules/vpc/aws/latest).
- **Put HTTPS in front of the app** with an Application Load Balancer and a free ACM certificate.
- **Explore the [Terraform Registry](https://registry.terraform.io/)**, where thousands of modules solve problems you'd otherwise build yourself.
