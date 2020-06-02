This file explains how to get set up as an Acquia developer, working on Acquia
CMS itself. It does not cover how to install Acquia CMS and build a site with
it.

### Assumptions
These instructions assume that you have an Acquia Cloud account, and that you
are using a *nix-type command line environment (e.g. Linux, macOS, or the
Windows 10 subsystem for Linux).

You should also have:
* PHP 7.3 or later installed. (`php --version`)
* An Acquia Cloud IDE entitlement. Check with your manager or technical
  architect to ensure that this is available to you.

### Background
To provide a consistent environment for our development team, Acquia CMS is
developed using Acquia's Cloud IDE service, which provides a VSCode-like
developer experience. It is possible to work on Acquia CMS on your own machine,
using your IDE of choice, but this file doesn't cover that.

### Instructions
1. Visit https://github.com/acquia/cli#installation and follow the instructions
to get the Acquia CLI tool installed. For example:
```
curl -OL https://github.com/acquia/cli/releases/download/v1.0.0-beta1/acli.phar
sudo mv acli.phar /usr/local/bin/acli
chmod +x /usr/local/bin/acli
acli --version
# You should see a version here. If not, be sure that /usr/local/bin is in your PATH.
```
2. Run `acli auth:login` and follow the prompts. You will need to log in to your
Acquia Cloud account and create a new API key and token, which you should save in a
safe place. (You'll need them later.)
3. Run `acli ide:create` and you will get a list of subscriptions to which you have
access. You should see "Acquia Engineering" in there somewhere; enter the number for
that one. If you are asked to link the cloud application to a repository, say "no".
Enter a personally identifying label for your IDE, like "phenaproxima Acquia CMS".
3. Wait 5 to 10 minutes while DNS propagates; go play with a puppy or have a cup of
coffee. When propagation is done, you will see the URLs for your IDE and the Drupal
site it is linked to, respectively. (Depending on your ISP and location, the CLI tool
can time out while waiting for DNS propagation. If you encounter an error, try
switching to an alternative DNS server. See https://docs.acquia.com/dev-studio/ide/known-issues/#creating-a-remote-ide-may-time-out-due-to-dns-propagation
for more information. Don't worry -- your IDE is still being provisioned and will be
accessible. Just run `acli ide:list` to see the IDEs in the subscription, which will
include the URLs.)
4. Run `acli ide:open`. Choose the "Acquia Engineering" subscription, and the IDE you
just created. It should open in your browser and you should see a "getting started"
page.
5. Click the "Setup ADS CLI" button and follow the prompts. You'll need to enter the
API key and token you created in step 2.
6. Click the "Generate SSH key" button. When asked for a password, enter one that
you can remember. When asked if you want to upload the SSH key to Acquia Cloud, say
yes. Label your SSH key similarly to how you labeled the IDE, e.g.
`phenaproxima_AcquiaCMS`, and upload it to Acquia Cloud. When prompted for the
passphrase, enter the password you just created.
7. Run `cat ~/.ssh/id_rsa.pub`. Copy the SSH key and add it to your GitHub
account. See https://docs.acquia.com/dev-studio/ide/start/#cloning-your-application-from-a-github-repository-to-your-ide
for more information. Be sure to enable SSO for the newly added key,
authorizing the Acquia organization.
8. In the Cloud IDE's terminal, clone the Acquia CMS Git repository:
```
git clone git@github.com:acquia/acquia_cms.git ~/project --branch develop
```
9. Install all dependencies:
```
cd project
composer install
composer run post-install-cmd
```
10. Install Drupal: `drush site:install acquia_cms --yes --account-pass admin`
11. In the "Open Drupal Site" menu, choose "Open site in a new tab" and ensure
you can see the Drupal site, and log in with the username "admin" and password
"admin".
