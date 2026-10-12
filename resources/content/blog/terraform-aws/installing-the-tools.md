You need three command-line tools on your machine. Only the first two are needed to run Terraform. The third is for connecting to the server afterwards.

| Tool | Why | Minimum version |
|---|---|---|
| Terraform | Runs the project | 1.10 |
| AWS CLI | Signs you in to AWS and is used for a few one-off commands | v2 |
| Session Manager plugin | Opens a shell on the EC2 instance without SSH | Any current |

On Windows, the simplest route is to install everything inside [WSL](https://learn.microsoft.com/windows/wsl/install) and follow the Linux instructions.

## Terraform

The project requires **Terraform 1.10 or newer**. That's the release that added S3-native state locking, which the backend configuration relies on. The [official install page](https://developer.hashicorp.com/terraform/install) has packages for every platform. The most common options are below.

**macOS** (Homebrew):

```bash
brew tap hashicorp/tap
brew install hashicorp/tap/terraform
```

**Ubuntu / Debian / WSL** (HashiCorp's apt repository):

```bash
sudo apt-get update && sudo apt-get install -y gnupg software-properties-common curl
curl -fsSL https://apt.releases.hashicorp.com/gpg | sudo gpg --dearmor -o /usr/share/keyrings/hashicorp-archive-keyring.gpg
echo "deb [signed-by=/usr/share/keyrings/hashicorp-archive-keyring.gpg] https://apt.releases.hashicorp.com $(lsb_release -cs) main" \
  | sudo tee /etc/apt/sources.list.d/hashicorp.list
sudo apt-get update && sudo apt-get install -y terraform
```

**Windows** (without WSL):

```powershell
winget install Hashicorp.Terraform
```

Check that it worked:

```bash
terraform -version
```

You should see `Terraform v1.10.0` or later.

> **Tip:** If you work on several projects that need different Terraform versions, [tfenv](https://github.com/tfutils/tfenv) lets you install several side by side and switch between them.

## AWS CLI

The [AWS CLI](https://docs.aws.amazon.com/cli/latest/userguide/cli-chap-welcome.html) is how you sign in. Terraform reads the credentials it sets up, so you rarely call it directly. Make sure you install **version 2**. Version 1 doesn't support single sign-on.

**macOS:**

```bash
brew install awscli
```

**Linux / WSL** (x86_64):

```bash
curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o awscliv2.zip
unzip awscliv2.zip
sudo ./aws/install
```

**Windows:** download and run the [MSI installer](https://awscli.amazonaws.com/AWSCLIV2.msi).

Other platforms and architectures are covered in the [AWS CLI install guide](https://docs.aws.amazon.com/cli/latest/userguide/getting-started-install.html). Check it:

```bash
aws --version
```

The output should start with `aws-cli/2.`.

## Session Manager plugin

[AWS Systems Manager Session Manager](https://docs.aws.amazon.com/systems-manager/latest/userguide/session-manager.html) gives you a shell on an EC2 instance through the AWS API instead of SSH. There are no keys to manage and no open ports, and every session is logged. The AWS CLI needs a small plugin to use it.

**macOS:**

```bash
brew install --cask session-manager-plugin
```

**Ubuntu / Debian / WSL:**

```bash
curl "https://s3.amazonaws.com/session-manager-downloads/plugin/latest/ubuntu_64bit/session-manager-plugin.deb" -o session-manager-plugin.deb
sudo dpkg -i session-manager-plugin.deb
```

Other platforms are in the [plugin install guide](https://docs.aws.amazon.com/systems-manager/latest/userguide/session-manager-working-with-install-plugin.html). Check it:

```bash
session-manager-plugin --version
```

## Optional extras

- **Docker**, to build and push the application image. You'll need it in [Using your infrastructure](/blog/terraform-aws/using-your-infrastructure).
- **jq**, a command-line JSON processor that's handy for pulling values out of `terraform output -json`.
- **Editor support.** The official [HashiCorp Terraform extension](https://marketplace.visualstudio.com/items?itemName=HashiCorp.terraform) for VS Code adds syntax highlighting, autocompletion and inline documentation for every AWS resource.
