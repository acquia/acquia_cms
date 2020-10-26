This file explains how to get set up as an Acquia developer, working on Acquia CMS itself. It does not explain how to get set up to build a "real" project with Acquia CMS.

### Assumptions
These instructions assume that you have an Acquia Cloud account, and that you are using a nix-type command line environment (e.g. Linux, macOS, or the Windows 10 subsystem for Linux).

You should also have:
* PHP 7.3 or later installed. (`php --version`)
* Composer 1.9.2 or later. (`composer --version`)
* An invitation to the Acquia Engineering subscription in Acquia Cloud. Your manager or technical architect should be able to get you an invitation to this subscription if you need one.
* A GitHub account which is authorized within the Acquia organization and can access https://github.com/acquia/acquia_cms.

### Background
To provide a consistent environment for our development team, Acquia CMS is developed using Acquia's Cloud IDE service, which provides a VSCode-like developer experience. It is possible to work on Acquia CMS on your own machine, using your IDE of choice, but that is not the recommended set-up in most circumstances.

### Setting up a Cloud IDE
Because there is a limited number of Cloud IDEs available to the Acquia CMS team, each active developer should only need (and have) one. Therefore, you should only do this once.

1. Visit https://github.com/acquia/cli#installation and follow the instructions to get the Acquia CLI tool installed. For example:
```
curl -OL https://github.com/acquia/cli/releases/download/v1.0.0-rc3/acli.phar
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
```
11. Install Acquia CMS, as detailed in the "Installing Acquia CMS" section below.
12. In the "Open Drupal Site" menu, choose "Open site in a new tab" and ensure you can see the Drupal site, and log in with the username "admin" and password "admin".

### Installing Acquia CMS

#### Installing from the Command Line
For development purposes, it's easiest to install Acquia CMS at the command line using Drush. In these instructions, I assume that you have the [Drush launcher](https://github.com/drush-ops/drush-launcher) installed globally in your PATH (`drush --version`).

To save time and resources, Acquia CMS will not by default import any templates from Cohesion during installation. If you want to automatically import Cohesion templates during installation, you'll need to provide the Cohesion API key and organization key, which you can get from your manager or technical architect, as environment variables:
```
export SITESTUDIO_API_KEY=foo
export SITESTUDIO_ORG_KEY=bar
```
Cloud IDEs come with a preconfigured MySQL database, so to install Acquia CMS on a Cloud IDE, simply run `drush site:install acquia_cms --yes --account-pass admin`.

It can take a lot of memory to install Acquia CMS. If you run into memory errors, try increasing the memory limit when installing Acquia CMS:
```
php -d memory_limit=2G vendor/bin/drush site:install acquia_cms --yes --account-pass admin
```
If 2 GB *still* isn't enough memory, try raising the limit even more.

#### Installing through the Browser
Due to some of the work being done on Acquia CMS (specifically related to installation tasks) it may be necessary to do a manual install through the browser.

In this case, you will need to manually drop your existing database with mysql and then re-visit the site via your browser.

For Cloud IDEs that can be accomplished with:
```
mysql -u root
drop database drupal;
exit
```

### Running tests
Acquia CMS's tests are written using the PHPUnit-based framework provided by Drupal core. To run tests, we have provided a shell script that automatically executes all code validation and tests in a single command.

From the repository root, simply run:

```
./acms-run-tests.sh
```

If you want to run tests in a more ad-hoc or one-off fashion, you need to do a bit of set-up:

1. From the repository root, use PHP's built-in web server to serve the Drupal site: `drush runserver 8080`. You can use a different server if you want to; just be sure to adjust the `SIMPLETEST_BASE_URL` environment variable (described below) as needed. To run functional JavaScript tests, be sure that you have Chrome and [ChromeDriver](https://sites.google.com/a/chromium.org/chromedriver) installed and running. You can start ChromeDriver in a new terminal window with `chromedriver --port=4444`. (You can use any port you want, but 4444 is standard.) Note that **ChromeDriver must be running on the same host as Chrome itself!**
2. In a new terminal window, define a few environment variables:
```
# The URL of the database you're using. This is the URL for the database in your cloud IDE, so it may differ in a local environment.
export SIMPLETEST_DB=mysql://drupal:drupal@127.0.0.1/drupal

# The URL where you can access the Drupal site. This must be set twice in order to support both the built-in PHPUnit test framework and the Drupal Test Traits framework.
export SIMPLETEST_BASE_URL=http://127.0.0.1:8080
export DTT_BASE_URL=$SIMPLETEST_BASE_URL

# Optional: silence deprecation errors, which can be very distracting when debugging test failures.
export SYMFONY_DEPRECATIONS_HELPER=weak

# Set the options for running functional JavaScript tests through ChromeDriver. This must be set twice in order to support both the built-in PHPUnit test framework and the Drupal Test Traits framework. If needed, you can change the port at which ChromeDriver is listening. To watch the tests run in the GUI (usually only possible on a local development environment), remove the "headless" switch.
export MINK_DRIVER_ARGS_WEBDRIVER='["chrome", {"chrome": {"switches": ["headless"]}}, "http://127.0.0.1:4444"]'
export DTT_MINK_DRIVER_ARGS=$MINK_DRIVER_ARGS_WEBDRIVER
```

To run all Acquia CMS tests (which may take a while), use this command:
```
cd docroot
../vendor/bin/phpunit -c core --group acquia_cms --debug
```
To run all tests for a particular module:
```
cd docroot
../vendor/bin/phpunit -c core --group MODULE --debug
```
To run a particular test:
```
cd docroot
../vendor/bin/phpunit -c core --debug profiles/acquia_cms/modules/acquia_cms_page/tests/src/Functional/PageTest.php
```

### Coding standards
Compliance with Acquia's coding standards is automatically checked on commit; however, only changed files are analyzed. Our CI process does a thorough scan of the entire code base, and will fail if any problems are found. If you want to check coding standards compliance across the entire code base before submitting a pull request, run `vendor/bin/grumphp run`.

### Setting up a local environment (optional)
In certain situations, it may be helpful to set up a local development environment. However, you should only do this if you really need to.

In addition to the prequisites listed at the top of this file, you'll need:
* Composer (`composer --version`)
* Git (`git --version`)

Clone the repository and install all dependencies:
```
git clone git@github.com:acquia/acquia_cms.git --branch develop
cd acquia_cms
composer install
```
Then, install Acquia CMS as detailed in the "Installing Acquia CMS" section above.

Once you've installed Acquia CMS, how you serve it is up to you. For local development, the most convenient option is PHP's built-in web server: `drush runserver 8080`.

### Contributing
Contributing to Acquia CMS requires the ability to push branches to the repository, since we do not use forks. If you need access, ask your manager or technical architect.

Before opening a new branch, note the JIRA ticket number that you're going to work on (there can be multiple branches associated with a single ticket). The ticket number will have the format ACMS-N, where N is a number. The branch name should be prefixed by the ticket number, followed by a short description, and it should be branched from the `develop` branch. For example:
```
git checkout develop
git pull
git checkout -b ACMS-35/event-content-type
```
Once the branch is open, you can make as many commits to it as you like. All commit messages must be prefixed by the ticket number. For example: `ACMS-35: Add the event content type` (note the lack of a period at the end of the commit message) is a good example of a commit message.

When you're ready for your work to be reviewed, open a pull request to merge your branch into the `develop` branch. You should also periodically sync and rebase against `develop`.

### Best practices
Here are a few things to keep in mind as you work to improve Acquia CMS's code and config:
* When adding or updating config that ships with Acquia CMS (either in the profile or one of its component modules), the UUID and `_core` section should always be removed, because they are specific to a single Drupal installation, and Acquia CMS's configuration needs to be generic. If exporting a single piece of config at the command line, you can use the `--generic` option to do this automatically. For example: `drush config:get --generic node.type.article`.
* When exporting a piece of config, you should review anything added to its `dependencies` section. Drupal core occasionally adds dependencies which are not "real" -- that is, things the config does not truly *need* in order to work correctly. There are no hard and fast rules for evaluating this; take your best guess, and ask another developer (or your technical architect) if you're stumped. A good rule of thumb is to ask yourself, for each dependency, "can this piece of config function work at all without this?"
* Similarly, you should only add a dependency to any given module if it is a true, hard dependency. If the module(s) you're modifying can function without a given dependency, don't add it to the dependencies -- add it to the profile's list of modules instead.
* Conversely, if you are adding a dependency or piece of config that ALL content types may or will need, it should go into the acquia_cms_common module, which all other Acquia CMS modules depend on.
* In Acquia CMS, entity display is managed by Cohesion. In most cases, therefore, we should not include any entity view displays in our config (i.e., files beginning with `core.entity_view_display`). If there *is* a special case where we need to ship an entity view display, we will mention it in the implementation details of the ticket.
* You'll find that Acquia CMS's `composer.lock` file is not tracked by Git. That's intentional: because this is a distribution, tight control of third-party dependencies is (generally) unnecessary. If you want Git to ignore the file, add it to the `.git/info/exclude` file, which will be in your local clone of the repository.
* If anything is unclear, don't hesitate to ask for clarification! :)
