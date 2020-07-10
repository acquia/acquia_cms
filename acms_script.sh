#!/usr/bin/env bash
set -e
# This script gets executed on running composer command acms-test.
# This is a single command to execute code validation and automated testing.

# Run code quality checks.s
vendor/bin/grumphp run
# Kils 8080 server process, if already running.
fuser -k 8080/tcp
# Run 8080 server in backgorund.
drush runserver 8080 &
echo "Server started"
# Set the URL of the database being used.
export SIMPLETEST_DB=mysql://drupal:drupal@127.0.0.1/drupal
# Set the URL where you can access the Drupal site.
export SIMPLETEST_BASE_URL=http://127.0.0.1:8080
# Silence deprecation errors.
export SYMFONY_DEPRECATIONS_HELPER=weak
# Run phpunit automation test for acquia_cms group.
echo "Running phpunit tests for acquia_cms group."
COMPOSER_PROCESS_TIMEOUT=0 phpunit -c docroot/core --group acquia_cms
# Stop 8080 server.
fuser -k 8080/tcp

