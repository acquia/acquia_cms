#!/usr/bin/env bash
set -e
echo Running on $OSTYPE
# This script gets executed on running ./acms-test.sh from project folder.
# This is a single command to execute code validation and automated testing.

# Starting drush server and chrome driver, if they are not already running.
# This is required to run phpunit and javascript tests respectively.
# Assuming mink driver port is set to 9515 in phpunit.xml.
# Switch case to handle mac os and linux.
case $OSTYPE in
  "linux-gnu"*)
    if declare -a array=($(tail -n +2 /proc/net/tcp | cut -d":" -f"3"|cut -d" " -f"1")) &&
    for port in ${array[@]}; do echo $((0x$port)); done | grep 8080 ; then
      echo "Drush server is already running."
    else
      drush runserver 8080 &
      echo "Drush server started on port 8080."
    fi
    if declare -a array=($(tail -n +2 /proc/net/tcp | cut -d":" -f"3"|cut -d" " -f"1")) &&
    for port in ${array[@]}; do echo $((0x$port)); done | grep 9515 ; then
      echo "Chrome driver is already running."
      else
      chromedriver --url-base=/wd/hub &
      echo "Chromedriver started on port 9515."
    fi
      ;;
  "darwin"*)
      if [ -z "$(lsof -t -i:8080)" ] ; then
       drush runserver 8080 &
       echo "Drush server started on port 8080."
      else
       echo "Drush server is already running."
      fi
      if [ -z "$(lsof -t -i:9515)" ] ; then
      chromedriver --url-base=/wd/hub &
      echo "Chromedriver started on port 9515."
      else
       echo "Chrome driver already running."
      fi
      ;;
esac

# Run code quality checks.
vendor/bin/grumphp run &
echo "Quality checks completed."

# Set the URL of the database being used,
# only if it is not set, and display it's value.
if [ -z "$(printenv SIMPLETEST_DB)" ] ; then
  export SIMPLETEST_DB=mysql://drupal:drupal@127.0.0.1/drupal
  echo "If you are using sqlite, set environment variable accordingly, ex (sqlite://localhost/drupal.sqlite)"
  echo "SIMPLETEST_DB environment variable is now set as:"
  printenv SIMPLETEST_DB
else
  echo "SIMPLETEST_DB environment variable is already set as:"
  printenv SIMPLETEST_DB
fi

# Set the URL where you can access the Drupal site,
# only if it is not set, and display it's value.
if [ -z "$(printenv SIMPLETEST_BASE_URL)" ] ; then
  export SIMPLETEST_BASE_URL=http://127.0.0.1:8080
  echo "SIMPLETEST_BASE_URL environment variable is now set as:"
  printenv SIMPLETEST_BASE_URL
  else
  echo "SIMPLETEST_BASE_URL environment variable is already set as:"
  printenv SIMPLETEST_BASE_URL
fi

# Silence deprecation errors.
# only if it is not set, and display it's value.
if [ -z "$(printenv SYMFONY_DEPRECATIONS_HELPER)" ] ; then
  export SYMFONY_DEPRECATIONS_HELPER=weak
  echo "SYMFONY_DEPRECATIONS_HELPER environment variable is now set as:"
  printenv SYMFONY_DEPRECATIONS_HELPER
  else
  echo "SYMFONY_DEPRECATIONS_HELPER environment variable is already set as:"
  printenv SYMFONY_DEPRECATIONS_HELPER
fi

# Run all automated PHPUnit tests.
# If --stop-on-failure is passed as an argument $1 will handle it.
echo "Running phpunit tests for acquia_cms group."
COMPOSER_PROCESS_TIMEOUT=0 ./vendor/bin/phpunit -c docroot/core --group acquia_cms $1

# Stop Chrome driver and drush server.
# Check if fuser is present and stop processes for port 8080 and 9515.
# Check OS type to use fuser or kill accordingly.

case $OSTYPE in
   "linux-gnu"*)
     if command -v fuser &> /dev/null
     then
       fuser -k 8080/tcp
       echo 'Killed process for port 8080 to stop drush server.'
       fuser -k 9515/tcp
      echo 'Killed process for port 9515, to stop chromedriver.'
     else
       echo 'Please install fuser';
      fi
    ;;
   "darwin"*)
      # Stop 8080 server.
      kill -9 $(lsof -t -i:8080)
      echo 'Killed process for port 8080 to stop drush server.'
      # Stop 9515 server.
      kill -9 $(lsof -t -i:9515)
      echo 'Killed process for port 9515, to stop chromedriver.'
    ;;
esac
