#!/usr/bin/env bash

# NAME
#     script.sh - Run ORCA tests.
#
# SYNOPSIS
#     script.sh
#
# DESCRIPTION
#     Runs static code analysis and automated tests.

cd "$(dirname "$0")"

# Reuse ORCA's own includes.
source ../../../orca/bin/travis/_includes.sh

if [ "$TRAVIS_JOB_NAME" != "Starter" ] && [ "$TRAVIS_JOB_NAME" != "PubSec Demo" ] && [ "$ACMS_JOB" != "base" ]; then
  # Run ORCA's standard script.
  ../../../orca/bin/travis/script.sh
fi

if [ "$ACMS_JOB" = "base" ]; then
  cd "$(dirname "$0")" || exit; source _includes.sh

  [[ ! -d "$ORCA_FIXTURE_DIR" ]] || orca fixture:status
  '/usr/bin/git' 'checkout' '--force' 'fresh-fixture'
  '/usr/bin/git' 'clean' '--force' '-d'
  '/home/travis/build/acquia/orca-build/vendor/bin/phpunit' '--verbose' '--colors=always' '--debug' '--configuration=/home/travis/build/acquia/orca-build/docroot/core/phpunit.xml' '--exclude-group=orca_ignore,site_studio' '--testsuite=orca'
fi

# If there is no fixture, there's nothing else for us to do.
[[ -d "$ORCA_FIXTURE_DIR" ]] || exit 0

cd $ORCA_FIXTURE_DIR
if [ "$TRAVIS_JOB_NAME" == "Starter" ]; then
  # Install npm dependencies and run JS test suites.
  cd $TRAVIS_BUILD_DIR
  npm install
  orca fixture:run-server &
  # Runs Backstop.js
   npm run backstop-starter
  # Runs Pa11y.js
  npm run pa11y-starter
fi
