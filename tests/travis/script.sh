#!/usr/bin/env bash

cd "$(dirname "$0")"

# Reuse ORCA's own includes.
source ../../../orca/bin/travis/_includes.sh

# Run ORCA's standard post-success script.
../../../orca/bin/travis/script.sh


cd $ORCA_FIXTURE_DIR
drush pm-enable --yes acquia_cms_demo_pubsec

cd $TRAVIS_BUILD_DIR
npm install
orca fixture:run-server &
npm run tests
