#!/usr/bin/env bash

# NAME
#     install.sh - Install Travis CI dependencies
#
# SYNOPSIS
#     install.sh
#
# DESCRIPTION
#     Creates the test fixture.

cd "$(dirname "$0")"

# Reuse ORCA's own includes.
source ../../../orca/bin/travis/_includes.sh

# Run ORCA's standard installation script.
../../../orca/bin/travis/install.sh

# If there is no fixture, there's nothing else for us to do.
[[ -d "$ORCA_FIXTURE_DIR" ]] || exit 0

cd $ORCA_FIXTURE_DIR

# Install dev dependencies.
composer require --dev weitzman/drupal-test-traits

# If there is a pre-built archive of code, assets, and templates for
# Cohesion, import that instead of calling out to Cohesion's API.
if [ ! -z $COHESION_ARTIFACT ] && [ -f $COHESION_ARTIFACT ]; then
  tar -x -z -v -f $COHESION_ARTIFACT --directory docroot/sites/default/files
  drush config:import --yes --partial --source sites/default/files/cohesion/config
fi

# In order for PHPUnit tests belonging to profile modules to even be
# runnable, the profile's modules need to be symlinked into the
# sites/all/modules directory. This is a long-standing limitation of
# Drupal core (10 year old issue) that shows no signs of being fixed
# any time soon. We do a similar workaround in our composer.json's
# post-install-cmd script.
cd docroot/sites
mkdir -p ./all/modules
cd ./all/modules
find ../../../profiles/contrib/acquia_cms/modules -maxdepth 1 -mindepth 1 -type d -exec ln -s -f '{}' ';'
# Ensure the symlinks are included in the ORCA fixture snapshot.
git add .

# Set the fixture state to reset to between tests.
orca fixture:backup --force
