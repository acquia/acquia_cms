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

# Run ORCA's standard script.
../../../orca/bin/travis/script.sh

case "$ORCA_JOB" in
  cd $ORCA_FIXTURE_DIR
  drush pm-enable --yes acquia_cms_demo_pubsec

  cd $TRAVIS_BUILD_DIR
  npm install
  orca fixture:run-server &
  npm run tests
esac
