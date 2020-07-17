#!/usr/bin/env bash

# Automatically abort the script if any errors occur.
set -e

RED="\033[1;31m"
GREEN="\033[1;32m"
YELLOW="\033[1;33m"
NOCOLOR="\033[0m"

WEBSERVER_PORT=8080
CHROMEDRIVER_PORT=4444

echo -e "${GREEN}Running on ${OSTYPE}${NOCOLOR}"

# This script can be executed by running ./acms-run-tests.sh from project folder. It will execute all Acquia CMS tests and quality checks for you.

# Start PHP's built-in http server on port "${WEBSERVER_PORT}".
runwebserver() {
  echo -e "${YELLOW}Starting PHP's built-in http server on "${WEBSERVER_PORT}".${NOCOLOR}"
  nohup drush runserver "${WEBSERVER_PORT}" &
  echo -e "${GREEN}Drush server started on port "${WEBSERVER_PORT}".${NOCOLOR}"
}

# Run ChromeDriver on port "${CHROMEDRIVER_PORT}".
runchromedriver() {
  echo -e "${YELLOW}Starting ChromeDriver on port "${CHROMEDRIVER_PORT}".${NOCOLOR}"
  nohup chromedriver --port="${CHROMEDRIVER_PORT}" &
  echo -e "${GREEN}Started ChromeDriver on port "${CHROMEDRIVER_PORT}".${NOCOLOR}"
}

# Kill any process on a linux GNU environment.
killProcessLinuxOs() {
  if command -v fuser &> /dev/null
    then
      fuser -k "${1}/tcp"
      echo -e "${YELLOW}Process killed on port $1 ${NOCOLOR}"
    else
      echo 'Please install fuser';
      exit 1
    fi
}

# Kill any process on a Darwin environment.
killProcessDarwinOs() {
  nohup kill -9 $(lsof -t -i:${1})
  echo -e "${YELLOW}Process killed on port $1 ${NOCOLOR}"
}

# Switch case to handle macOS and Linux.
case $OSTYPE in
  "linux-gnu"*)
    if declare -a array=($(tail -n +2 /proc/net/tcp | cut -d":" -f"3"|cut -d" " -f"1")) &&
      for port in ${array[@]}; do echo $((0x$port)); done | grep "${WEBSERVER_PORT}" ; then
        echo -e "${RED}Port "${WEBSERVER_PORT}" is already occupied. Web server cannot start on port "${WEBSERVER_PORT}".${NOCOLOR}"
      else
        runwebserver
    fi
    if declare -a array=($(tail -n +2 /proc/net/tcp | cut -d":" -f"3"|cut -d" " -f"1")) &&
      for port in ${array[@]}; do echo $((0x$port)); done | grep "${CHROMEDRIVER_PORT}" ; then
        echo -e "${RED}Port "${CHROMEDRIVER_PORT}" is already occupied. ChromeDriver cannot run on port "${CHROMEDRIVER_PORT}". ${NOCOLOR}"
      else
        runchromedriver
    fi
      ;;
  "darwin"*)
      if [ -z "$(lsof -t -i:"${WEBSERVER_PORT}")" ] ; then
        runwebserver
      else
        echo -e "${RED}Port "${WEBSERVER_PORT}" is already occupied. Web server cannot start on port "${WEBSERVER_PORT}". ${NOCOLOR}"
      fi
      if [ -z "$(lsof -t -i:"${CHROMEDRIVER_PORT}")" ] ; then
        runchromedriver
      else
        echo -e "${RED}Port "${CHROMEDRIVER_PORT}" is already occupied. ChromeDriver cannot run on port "${CHROMEDRIVER_PORT}". ${NOCOLOR}"
      fi
      ;;
esac

# Run code quality checks.
vendor/bin/grumphp run

# Set SIMPLETEST_DB environment variable if it is not set already.
if [ -z "$(printenv SIMPLETEST_DB)" ] ; then
  export SIMPLETEST_DB=sqlite://localhost/drupal.sqlite
  echo -e "${GREEN}SIMPLETEST_DB environment variable is now set as: ${NOCOLOR}"
  printenv SIMPLETEST_DB
  echo -e "${YELLOW}If you are using MySQL or PostgreSQL, set the environment variable accordingly, e.g., mysql://drupal:drupal@127.0.0.1/drupal${NOCOLOR}"
fi

# Set SIMPLETEST_BASE_URL environment variable if it is not set already.
if [ -z "$(printenv SIMPLETEST_BASE_URL)" ] ; then
  export SIMPLETEST_BASE_URL=http://127.0.0.1:"${WEBSERVER_PORT}"
  echo -e "${GREEN}SIMPLETEST_BASE_URL environment variable is now set as: ${NOCOLOR}"
  printenv SIMPLETEST_BASE_URL
fi

# Set DTT_BASE_URL environment variable if it is not set already.
if [ -z "$(printenv DTT_BASE_URL)" ] ; then
  export DTT_BASE_URL=$SIMPLETEST_BASE_URL
  echo -e "${GREEN}DTT_BASE_URL environment variable is now set as: ${NOCOLOR}"
  printenv DTT_BASE_URL
fi

# Set MINK_DRIVER_ARGS_WEBDRIVER environment variable if it is not set already.
if [ -z "$(printenv MINK_DRIVER_ARGS_WEBDRIVER)" ] ; then
  export MINK_DRIVER_ARGS_WEBDRIVER='["chrome", {"chrome": {"switches": ["headless"]}}, "http://127.0.0.1:4444"]'
  echo -e "${GREEN}MINK_DRIVER_ARGS_WEBDRIVER environment variable is now set as: ${NOCOLOR}"
  printenv MINK_DRIVER_ARGS_WEBDRIVER
fi

# Set DTT_MINK_DRIVER_ARGS environment variable if it is not set already.
if [ -z "$(printenv DTT_MINK_DRIVER_ARGS)" ] ; then
  export DTT_MINK_DRIVER_ARGS=$MINK_DRIVER_ARGS_WEBDRIVER
  echo -e "${GREEN}DTT_MINK_DRIVER_ARGS environment variable is now set as: ${NOCOLOR}"
  printenv DTT_MINK_DRIVER_ARGS
fi

# Set SYMFONY_DEPRECATIONS_HELPER environment variable if it is not set already.
if [ -z "$(printenv SYMFONY_DEPRECATIONS_HELPER)" ] ; then
  export SYMFONY_DEPRECATIONS_HELPER=weak
  echo -e "${GREEN}SYMFONY_DEPRECATIONS_HELPER environment variable is now set as:${NOCOLOR}"
  printenv SYMFONY_DEPRECATIONS_HELPER
fi

# Run all automated PHPUnit tests.
# If --stop-on-failure is passed as an argument $1 will handle it.
echo -e "${YELLOW}Running phpunit tests for acquia_cms. ${NOCOLOR}"
COMPOSER_PROCESS_TIMEOUT=0 ./vendor/bin/phpunit -c docroot/core docroot/profiles/acquia_cms --debug $1

# Stop ChromeDriver and Drush web server based on OS Type.
case $OSTYPE in
   "linux-gnu"*)
    echo -e "${YELLOW}Stopping drush webserver.${NOCOLOR}"
    killProcessLinuxOs "${WEBSERVER_PORT}"
    echo -e "${YELLOW}Stopping chromedriver.${NOCOLOR}"
    killProcessLinuxOs "${CHROMEDRIVER_PORT}"
    ;;
   "darwin"*)
    echo -e "${YELLOW}Stopping drush webserver.${NOCOLOR}"
    killProcessDarwinOs "${WEBSERVER_PORT}"
    echo -e "${YELLOW}Stopping chromedriver.${NOCOLOR}"
    killProcessDarwinOs "${CHROMEDRIVER_PORT}"
    ;;
esac
