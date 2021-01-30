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

if [ "$ACMS_JOB" == "base_full" ]; then
  # Run ORCA's standard script.
  ../../../orca/bin/travis/script.sh
fi

if [ "$ACMS_JOB" == "base" ]; then
/home/travis/build/acquia/orca/bin/orca fixture:status
/home/travis/build/acquia/orca-build/vendor/bin/phpunit --exclude-group orca_ignore,low_risk --testsuite=orca
fi

# If there is no fixture, there's nothing else for us to do.
[[ -d "$ORCA_FIXTURE_DIR" ]] || exit 0

cd $ORCA_FIXTURE_DIR
if [ "$TRAVIS_JOB_NAME" == "Starter" ] || [ "$ACMS_JOB" == "starter_full" ]; then
  # Install npm dependencies and run JS test suites.
  cd $TRAVIS_BUILD_DIR
  npm install
  orca fixture:run-server &
  # Runs Backstop.js
   npm run backstop-starter
  # Runs Pa11y.js
  npm run pa11y-starter
fi
