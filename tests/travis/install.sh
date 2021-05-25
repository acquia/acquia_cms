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

# If running our custom jobs, initialize the fixture. Otherwise, use Orca's
# installation script.
if [[ "$ACMS_JOB" == "base" ]] || [[ "$ACMS_JOB" == "starter" ]]; then
  orca debug:packages CURRENT_DEV
  orca fixture:init --force --sut=acquia/acquia_cms --sut-only --core=CURRENT_DEV --dev --profile=acquia_cms --no-sqlite --no-site-install
elif [[ "$ACMS_JOB" == "base_full" ]] || [[ "$ACMS_JOB" == "starter_full" ]]; then
  orca debug:packages CURRENT_DEV
  orca fixture:init --force --sut=acquia/acquia_cms --sut-only --core=CURRENT_DEV --dev --profile=acquia_cms --no-sqlite
elif [[ "$ORCA_JOB" == "ISOLATED_TEST_ON_NEXT_MINOR" ]]; then
  orca debug:packages NEXT_MINOR
  orca fixture:init --force --sut=acquia/acquia_cms --sut-only --core=NEXT_MINOR --dev --profile=acquia_cms --no-sqlite
elif [[ "$ORCA_JOB" == "ISOLATED_TEST_ON_NEXT_MINOR_DEV" ]]; then
  orca debug:packages NEXT_MINOR_DEV
  orca fixture:init --force --sut=acquia/acquia_cms --sut-only --core=NEXT_MINOR_DEV --dev --profile=acquia_cms --no-sqlite
else
  # Run ORCA's standard installation script.
  ../../../orca/bin/travis/install.sh
fi

printenv | grep ACMS_ | sort

# If there is no fixture, there's nothing else for us to do.
[[ -d "$ORCA_FIXTURE_DIR" ]] || exit 0

cd $ORCA_FIXTURE_DIR

# Rebuild cohesion after install.
if [[ "$ACMS_JOB" == "base_full" ]] || [[ "$ACMS_JOB" == "starter_full" ]] || [[ "$ORCA_JOB" == "ISOLATED_TEST_ON_NEXT_MINOR" ]]; then
  drush cohesion:rebuild -y
fi

# Install dev dependencies.
composer require --dev weitzman/drupal-test-traits phpspec/prophecy-phpunit:^2

# If there is a pre-built archive of code, assets, and templates for
# Cohesion, import that instead of calling out to Cohesion's API.
if [ ! -z $COHESION_ARTIFACT ] && [ -f $COHESION_ARTIFACT ]; then
  tar -x -z -v -f $COHESION_ARTIFACT --directory docroot/sites/default/files
  drush config:import --yes --partial --source sites/default/files/cohesion/config
fi

# Base and Starter jobs should test against sites installed from
# artifacts to save build time.
if [[ "$ACMS_JOB" == "base" ]] && [[ -n "$ACMS_DB_ARTIFACT" ]] && [[ -n "$ACMS_FILES_ARTIFACT" ]] && [[ -f "$ACMS_DB_ARTIFACT" ]] && [[ -f "$ACMS_FILES_ARTIFACT" ]]; then
  echo "Installing From Artifacts"
  tar -xzf $ACMS_FILES_ARTIFACT
  gunzip $ACMS_DB_ARTIFACT
  drush sql:cli < $TRAVIS_BUILD_DIR/tests/acms.sql
  drush updatedb --cache-clear --yes -vvv
  drush cr
fi

# Use Starter artifacts if appropriate.
if [[ "$ACMS_JOB" == "starter" ]] && [[ -n "$ACMS_STARTER_DB_ARTIFACT" ]] && [[ -n "$ACMS_STARTER_FILES_ARTIFACT" ]] && [[ -f "$ACMS_STARTER_DB_ARTIFACT" ]] && [[ -f "$ACMS_STARTER_FILES_ARTIFACT" ]]; then
  echo "Installing Starter From Artifacts"
  tar -xzf $ACMS_STARTER_FILES_ARTIFACT
  gunzip $ACMS_STARTER_DB_ARTIFACT
  drush sql:cli < $TRAVIS_BUILD_DIR/tests/acms-starter.sql
  drush updatedb --cache-clear --yes -vvv
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

# Enable Starter on full installs if Appropriate.
if [[ "$ACMS_JOB" == "starter_full" ]]; then
    echo "Installing Starter Kit"
    drush en acquia_cms_development -y
    drush pmu shield -y
    drush en acquia_cms_starter -y
fi

# Set the fixture state to reset to between tests.
orca fixture:backup --force
