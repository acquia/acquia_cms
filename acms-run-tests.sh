#!/usr/bin/env bash
set -e

RED="\033[1;31m"
GREEN="\033[1;32m"
YELLOW="\033[1;33m"
NOCOLOR="\033[0m"

echo -e "${GREEN}Running on ${OSTYPE}${NOCOLOR}"

# This script can be executed by running ./acms-run-tests.sh from project folder. It will execute all Acquia CMS tests and quality checks for you.

# Start PHP's built-in http server on port 8080.
runwebserver() {
  echo -e "${YELLOW}Starting PHP's built-in http server on 8080.${NOCOLOR}"
  nohup drush runserver 8080 &
  echo -e "${GREEN}Drush server started on port 8080.${NOCOLOR}"
}

# Run chromedriver on port 9515 (assuming mink driver port is set to 9515 in phpunit.xml).
runchromedriver() {
  echo -e "${YELLOW}Starting Chromedriver on port 9515.${NOCOLOR}"
  nohup chromedriver --url-base=/wd/hub &
  echo -e "${GREEN}Started Chromedriver on port 9515.${NOCOLOR}"
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

# Switch case to handle mac os and linux.
case $OSTYPE in
  "linux-gnu"*)
    if declare -a array=($(tail -n +2 /proc/net/tcp | cut -d":" -f"3"|cut -d" " -f"1")) &&
      for port in ${array[@]}; do echo $((0x$port)); done | grep 8080 ; then
        echo -e "${RED}Port 8080 is already occupied. Webserver cannot start on port 8080.${NOCOLOR}"
      else
        runwebserver
    fi
    if declare -a array=($(tail -n +2 /proc/net/tcp | cut -d":" -f"3"|cut -d" " -f"1")) &&
      for port in ${array[@]}; do echo $((0x$port)); done | grep 9515 ; then
        echo -e "${RED}Port 9515 is already occupied. Chromedriver cannot run on port 9515. ${NOCOLOR}"
      else
        runchromedriver
    fi
      ;;
  "darwin"*)
      if [ -z "$(lsof -t -i:8080)" ] ; then
        runwebserver
      else
        echo -e "${RED}Port 8080 is already occupied. Webserver cannot start on port 8080. ${NOCOLOR}"
      fi
      if [ -z "$(lsof -t -i:9515)" ] ; then
        runchromedriver
      else
        echo -e "${RED}Port 9515 is already occupied. Chromedriver cannot run on port 9515. ${NOCOLOR}"
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
  echo -e "${YELLOW}If you are using SQL, set environment variable accordingly, ex (mysql://drupal:drupal@127.0.0.1/drupal)${NOCOLOR}"
fi

# Set SIMPLETEST_BASE_URL environment variable if it is not set already.
if [ -z "$(printenv SIMPLETEST_BASE_URL)" ] ; then
  export SIMPLETEST_BASE_URL=http://127.0.0.1:8080
  echo -e "${GREEN}SIMPLETEST_BASE_URL environment variable is now set as: ${NOCOLOR}"
  printenv SIMPLETEST_BASE_URL
fi

# Set SYMFONY_DEPRECATIONS_HELPER environment variable if it is not set already.
if [ -z "$(printenv SYMFONY_DEPRECATIONS_HELPER)" ] ; then
  export SYMFONY_DEPRECATIONS_HELPER=weak
  echo -e "${GREEN}SYMFONY_DEPRECATIONS_HELPER environment variable is now set as:${NOCOLOR}"
  printenv SYMFONY_DEPRECATIONS_HELPER
fi

# Run all automated PHPUnit tests.
# If --stop-on-failure is passed as an argument $1 will handle it.
echo -e "${YELLOW}Running phpunit tests for acquia_cms group. ${NOCOLOR}"
COMPOSER_PROCESS_TIMEOUT=0 ./vendor/bin/phpunit -c docroot/core --group acquia_cms $1

# Stop Chrome driver and drush server based on OS Type.
case $OSTYPE in
   "linux-gnu"*)
    echo -e "${YELLOW}Stopping drush webserver.${NOCOLOR}"
    killProcessLinuxOs 8080
    echo -e "${YELLOW}Stopping chromedriver.${NOCOLOR}"
    killProcessLinuxOs 9515
    ;;
   "darwin"*)
    echo -e "${YELLOW}Stopping drush webserver.${NOCOLOR}"
    killProcessDarwinOs 8080
    echo -e "${YELLOW}Stopping chromedriver.${NOCOLOR}"
    killProcessDarwinOs 9515
    ;;
esac
