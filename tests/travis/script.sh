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

if [ "$TRAVIS_JOB_NAME" != "Starter" ] && [ "$ACMS_JOB" != "starter_full" ]; then
  # Run ORCA's standard script.
  ../../../orca/bin/travis/script.sh
fi

# If there is no fixture, there's nothing else for us to do.
[[ -d "$ORCA_FIXTURE_DIR" ]] || exit 0

cd $ORCA_FIXTURE_DIR
if [ "$TRAVIS_JOB_NAME" == "Starter" ] || [ "$ACMS_JOB" == "starter_full" ]; then
  # Install npm dependencies and run JS test suites.
  cd $TRAVIS_BUILD_DIR
  # Clear cache to give image styles a chance to warm up.
  drush cr
  npm install
  orca fixture:run-server &

  # Runs Backstop.js
  npm run backstop-starter -vvv
  # Runs Pa11y.js
  npm run pa11y-starter
fi
