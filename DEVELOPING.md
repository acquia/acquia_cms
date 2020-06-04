This file explains how to get set up as an Acquia developer, working on Acquia CMS itself. It does not cover how to install Acquia CMS and build a site with it.

### Assumptions
These instructions assume that you have an Acquia Cloud account, and that you are using a nix-type command line environment (e.g. Linux, macOS, or the Windows 10 subsystem for Linux).

You should also have:
* PHP 7.3 or later installed. (`php --version`)
* An invitation to the Acquia Engineering subscription in Acquia Cloud. Your manager or technical architect should be able to get you an invitation to this subscription if you need one.
* A GitHub account which is authorized within the Acquia organization and can access https://github.com/acquia/acquia_cms.

### Background
To provide a consistent environment for our development team, Acquia CMS is developed using Acquia's Cloud IDE service, which provides a VSCode-like developer experience. It is possible to work on Acquia CMS on your own machine, using your IDE of choice, but this file doesn't cover that.

### Setting up a Cloud IDE
Because there is a limited number of Cloud IDEs available to the Acquia CMS team, each active developer should only need (and have) one. Therefore, you should only do this once.

1. Visit https://github.com/acquia/cli#installation and follow the instructions to get the Acquia CLI tool installed. For example:
```
curl -OL https://github.com/acquia/cli/releases/download/v1.0.0-beta1/acli.phar
sudo mv acli.phar /usr/local/bin/acli
chmod +x /usr/local/bin/acli
acli --version
# You should see a version here. If not, be sure that /usr/local/bin is in your PATH.
```
2. Run `acli auth:login` and follow the prompts. You will need to log in to your Acquia Cloud account and create a new API key and token, which you should save in a safe place. (You'll need them later.)
3. Run `acli ide:create` and you will get a list of subscriptions to which you have access. You should see "Acquia Engineering" in there somewhere; enter the number for that one. If you are asked to link the cloud application to a repository, say "no". Enter a personally identifying label for your IDE, like "phenaproxima Acquia CMS".
4. Wait 5 to 10 minutes while DNS propagates; go play with a puppy or have a cup of coffee. When propagation is done, you will see the URLs for your IDE and the Drupal site it is linked to, respectively. (Depending on your ISP and location, the CLI tool can time out while waiting for DNS propagation. If you encounter an error, try switching to an alternative DNS server. See https://docs.acquia.com/dev-studio/ide/known-issues/#creating-a-remote-ide-may-time-out-due-to-dns-propagation for more information. Don't worry -- your IDE is still being provisioned and will be accessible. Just run `acli ide:list` to see the IDEs in the subscription, which will include the URLs.)
5. Run `acli ide:open`. Choose the "Acquia Engineering" subscription, and the IDE you just created. It should open in your browser and you should see a "getting started" page.
6. Click the "Setup ADS CLI" button and follow the prompts. You'll need to enter the API key and token you created in step 2.
7. Click the "Generate SSH key" button. When asked for a password, enter one that you can remember. When asked if you want to upload the SSH key to Acquia Cloud, say yes. Label your SSH key similarly to how you labeled the IDE, e.g. `phenaproxima_AcquiaCMS`, and upload it to Acquia Cloud. When prompted for the passphrase, enter the password you just created.
8. Run `cat ~/.ssh/id_rsa.pub`. Copy the SSH key and add it to your GitHub account. See https://docs.acquia.com/dev-studio/ide/start/#cloning-your-application-from-a-github-repository-to-your-ide for more information. Be sure to enable SSO for the newly added key, authorizing the Acquia organization.
9. In the Cloud IDE's terminal, clone the Acquia CMS Git repository: `git clone git@github.com:acquia/acquia_cms.git ~/project --branch develop`
10. Install all dependencies:
```
cd project
composer install
composer run post-install-cmd
```
10. Install Acquia CMS, as detailed in the "Installing Acquia CMS" section below.
11. In the "Open Drupal Site" menu, choose "Open site in a new tab" and ensure you can see the Drupal site, and log in with the username "admin" and password "admin".

### Installing Acquia CMS
For development purposes, it's easiest to install Acquia CMS at the command line using Drush. We assume that you have the [Drush launcher](https://github.com/drush-ops/drush-launcher) installed globally in your PATH (`drush --version`).

Note that, by default, Acquia CMS will not import any templates from Cohesion during installation. This is done in order to save time and sources. If you want to automatically import Cohesion templates during installation, you'll need to provide the Cohesion API key and organization key, which you can get from your manager or technical architect, as environment variables:
```
export COHESION_API_KEY=foo
export COHESION_ORG_KEY=bar
```
Cloud IDEs come with a preconfigured MySQL database. To install Drupal on a Cloud IDE, run `drush site:install acquia_cms --yes --account-pass admin`.

It can take a lot of memory to install Acquia CMS. If you run into memory errors, try increasing the memory limit when installing Acquia CMS:
```
php -d memory_limit=2G vendor/bin/drush site:install acquia_cms --yes --account-pass admin
```
If 2 GB *still* isn't enough memory, try raising the limit even more.

### Setting up a local environment (optional)
In certain cases, it may be helpful to set up a development environment locally. Generally speaking, you should only do this if you _need_ to.

You'll need:
* PHP 7.3 (`php --version`), ideally with SQLite
* Composer (`composer --version`)
* Git (`git --version`)
* Access to the Acquia CMS GitHub repository

Clone the repository and install all dependencies:
```
git clone git@github.com:acquia/acquia_cms.git --branch develop
cd acquia_cms
composer install
composer run post-install-cmd
```
Then, install Acquia CMS as detailed in the "Installing Acquia CMS" section above.

Note that, in a local environment, it can be more convenient to use a SQLite database over MySQL, since SQLite doesn't require any additional servers to be running. If you want to use SQLite, pass the `--db-url sqlite://drupal.sqlite` option to `drush site:install`.

Once you've installed Acquia CMS, how you serve it is up to you. For local development, the most convenient way is to use PHP's built-in web server: `drush runserver 8080`.
