Terraform makes AWS API calls on your behalf, so it needs credentials: proof of who you are and what you're allowed to do. This chapter sets those up safely, and you only have to do it once per machine.

The short version:

1. Have an AWS account with the root user locked down.
2. Create a separate identity with permission to manage infrastructure.
3. Sign in to that identity with the AWS CLI.
4. Check that it works.

## 1. Secure your AWS account

If you don't have an account yet, [create one](https://aws.amazon.com/free/). The email and password you sign up with belong to the **root user**, which has unlimited power over the account and can't be restricted.

Don't use the root user for day-to-day work, and never create access keys for it. Instead:

- Turn on [multi-factor authentication](https://docs.aws.amazon.com/IAM/latest/UserGuide/enable-virt-mfa-for-root.html) for the root user.
- Use it only to set up the identity in the next step, then put it away.

AWS's [root user best practices](https://docs.aws.amazon.com/IAM/latest/UserGuide/root-user-best-practices.html) cover this in more detail.

## 2. Create an identity for Terraform

There are two common ways to do this. Choose **IAM Identity Center** if you can. It gives you short-lived credentials that expire automatically. **IAM user access keys** are simpler, but they're long-lived secrets that you have to protect and rotate.

### Option A: IAM Identity Center (recommended)

[IAM Identity Center](https://docs.aws.amazon.com/singlesignon/latest/userguide/what-is.html) (formerly AWS SSO) gives you a sign-in portal. The CLI opens your browser, you sign in, and it receives temporary credentials.

1. Sign in to the console as the root user and open **IAM Identity Center**. Choose **Enable**.
2. Under **Users**, add a user for yourself. You'll get an email to set a password.
3. Under **Permission sets**, create one using the predefined **AdministratorAccess** policy.
4. Under **AWS accounts**, select your account, choose **Assign users or groups**, and assign your user with that permission set.
5. Copy the **AWS access portal URL** from the Identity Center dashboard. It looks like `https://d-1234567890.awsapps.com/start`.

> **Why AdministratorAccess?** Terraform has to create IAM roles and policies, which needs broad permissions. For a personal or learning account, administrator access is the practical choice. In a team setting you'd create a narrower permission set covering only S3, ECR, EC2 and IAM. Follow the [Identity Center getting started guide](https://docs.aws.amazon.com/singlesignon/latest/userguide/getting-started.html) for screenshots of each step.

### Option B: IAM user with access keys

1. In the console, open **IAM → Users → Create user**.
2. Attach the **AdministratorAccess** policy directly.
3. Open the user, go to **Security credentials** and choose **Create access key**. Select **Command Line Interface (CLI)** as the use case.
4. Copy the **Access key ID** and **Secret access key**. The secret is only shown once.

Treat these keys like a password. Never commit them to git, never paste them into code, and delete them when you no longer need them. See [managing access keys](https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_access-keys.html).

## 3. Configure the AWS CLI

Using a **named profile** keeps these credentials separate from any other AWS accounts you use. This guide uses a profile called `asa`.

### If you chose Identity Center

```bash
aws configure sso --profile asa
```

Answer the prompts:

```text
SSO session name (Recommended): asa
SSO start URL [None]: https://d-1234567890.awsapps.com/start
SSO region [None]: eu-west-2
SSO registration scopes [sso:account:access]:
```

The *SSO region* is the region where you enabled Identity Center, which isn't necessarily where you'll deploy. Your browser opens to approve the sign-in. Back in the terminal, choose your account and the AdministratorAccess role, then set:

```text
Default client Region [None]: eu-west-2
CLI default output format [None]: json
```

The credentials last a few hours. When they expire, sign in again with:

```bash
aws sso login --profile asa
```

Full details are in [Configuring IAM Identity Center authentication](https://docs.aws.amazon.com/cli/latest/userguide/cli-configure-sso.html).

### If you chose access keys

```bash
aws configure --profile asa
```

```text
AWS Access Key ID [None]: AKIA...
AWS Secret Access Key [None]: ****************
Default region name [None]: eu-west-2
Default output format [None]: json
```

The keys are saved in `~/.aws/credentials`.

## 4. Check it works

```bash
aws sts get-caller-identity --profile asa
```

You should see your account ID and the identity you're signed in as:

```json
{
    "UserId": "AROAEXAMPLEID:you@example.com",
    "Account": "123456789012",
    "Arn": "arn:aws:sts::123456789012:assumed-role/AWSReservedSSO_AdministratorAccess_abc123/you@example.com"
}
```

Note the **Account** number. Bucket names in this project include it.

## How Terraform finds your credentials

The AWS provider in `versions.tf` has no credentials in it, and that's deliberate. Credentials never belong in code. Instead, the provider looks in the same places the AWS CLI does, in order:

1. Environment variables such as `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY`.
2. The profile named in `AWS_PROFILE`, read from `~/.aws/config` and `~/.aws/credentials`.
3. The `default` profile.
4. An attached role, when Terraform itself runs on AWS (for example on EC2 or in CI).

So to point Terraform at your `asa` profile, set one environment variable in the terminal you'll run it from:

```bash
export AWS_PROFILE=asa
```

On Windows PowerShell, use `$Env:AWS_PROFILE = "asa"`.

Every `terraform` and `aws` command in that terminal now uses the profile, and you can leave off `--profile`. Add the line to your shell profile (`~/.bashrc` or `~/.zshrc`) if you don't want to type it each time. The provider documentation lists every [authentication option](https://registry.terraform.io/providers/hashicorp/aws/latest/docs#authentication-and-configuration).

## Choosing a region

Everything defaults to **eu-west-2** (London). To use a different region, you'll change it in three places as you go through the next chapters:

- `aws_region` in `bootstrap` (as a `-var` when you apply it).
- `region` in `environments/dev/backend.tf`. This must match the region where the state bucket was created.
- `aws_region` in `environments/dev/terraform.tfvars`.

Pick the region closest to your users. The [AWS regions list](https://docs.aws.amazon.com/global-infrastructure/latest/regions/aws-regions.html) shows what's available.

## Set a budget alert

Before creating anything, take two minutes to set up a cost alert. In the console, open **Billing and Cost Management → Budgets → Create budget**, choose the **Monthly cost budget** template and enter an amount, for example $30, and your email. AWS will email you if spending is forecast to go over. ([Docs](https://docs.aws.amazon.com/cost-management/latest/userguide/budgets-managing-costs.html))

You're now connected. Next, create the bucket that stores Terraform's state.
