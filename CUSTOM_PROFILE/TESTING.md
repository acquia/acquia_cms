### Running Tests

#### PHPUnit

Most of Acquia CMS's tests are written using the PHPUnit-based framework provided by Drupal core. To run tests, we have provided a shell script that automatically executes all code validation and tests in a single command.

First, ensure that the necessary environment variables are set. To get access to these variables, reach out to your technical lead or product owner.

```
export CONNECTOR_ID='[replaceme]'
export SEARCH_UUID='[replaceme]'
```

Then, from the repository root, simply run:

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

To run all Custom Profile tests (which may take a while), use this command:
```
cd docroot
../vendor/bin/phpunit -c core profiles/custom/CUSTOM_PROFILE/modules --debug
```
To run all tests for a particular module in your CUSTOM_PROFILE:
```
cd docroot
../vendor/bin/phpunit -c core profiles/custom/CUSTOM_PROFILE/modules/<MODULE> --debug
```
Example:
```
cd docroot
../vendor/bin/phpunit -c core profiles/contrib/acquia_cms/modules/acquia_cms_search --debug
```

To run a particular test:
```
cd docroot
../vendor/bin/phpunit -c core --debug profiles/custom/CUSTOM_PROFILE/modules/<MODULE>/tests/src/Functional/TestName.php
```

####  Note

If you are trying to run tests for one of the Acquia CMS modules (as mentioned in example command above). You may run into the following error -
```
PHP Fatal error:  Uncaught Error: Class 'Drupal\Tests\acquia_cms_common\ExistingSite\ContentTypeListTestBase' not found
```
To resolve the error, copy the [phpunit.xml](https://github.com/acquia/acquia_cms/blob/develop/phpunit.xml) file from Acquia CMS repo and place it under core directory of your drupal installation.

Example:
```
cp -f phpunit.xml ./docroot/core
```
