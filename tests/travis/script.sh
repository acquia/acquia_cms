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

# If there is no fixture, there's nothing else for us to do.
[[ -d "$ORCA_FIXTURE_DIR" ]] || exit 0

cd $ORCA_FIXTURE_DIR
# Install the demo site so we have a lot of material to run through
# pa11y.
drush pm-enable --yes acquia_cms_demo_pubsec

# Install npm dependencies and run pa11y.
cd $TRAVIS_BUILD_DIR
npm install
orca fixture:run-server &
npm run tests
