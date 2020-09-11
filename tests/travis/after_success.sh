#!/usr/bin/env bash

# NAME
#     after_success.sh - Perform post-success tasks.
#
# SYNOPSIS
#     after_success.sh
#
# DESCRIPTION
#     Conditionally sends code coverage data to Coveralls.

cd "$(dirname "$0")" || exit 1; source _includes.sh

# Run ORCA's standard post-success script.
../../../orca/bin/travis/after_success.sh

cd $TRAVIS_BUILD_DIR
npm install
drush pm-enable --yes acquia_cms_demo_pubsec
orca fixture:run-server &
npm run-tests
