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

printenv | grep ACMS_ | sort

# If there is no fixture, there's nothing else for us to do.
[[ -d "$ORCA_FIXTURE_DIR" ]] || exit 0

cd $ORCA_FIXTURE_DIR

# Install dev dependencies.
composer require --dev weitzman/drupal-test-traits  phpspec/prophecy-phpunit:^2

# If there is a pre-built archive of code, assets, and templates for
# Cohesion, import that instead of calling out to Cohesion's API.
if [ ! -z $COHESION_ARTIFACT ] && [ -f $COHESION_ARTIFACT ]; then
  tar -x -z -v -f $COHESION_ARTIFACT --directory docroot/sites/default/files
  drush config:import --yes --partial --source sites/default/files/cohesion/config
fi

if [[ "$ACMS_JOB" == "base" ]] && [[ -n "$ACMS_DB_ARTIFACT" ]] && [[ -n "$ACMS_FILES_ARTIFACT" ]] && [[ -f "$ACMS_DB_ARTIFACT" ]] && [[ -f "$ACMS_FILES_ARTIFACT" ]]; then
    echo "Installing From Artifacts"
    tar -xzvf $ACMS_FILES_ARTIFACT
    DB="$ACMS_DB_ARTIFACT"
    tar -xzvf $db
    drush sql:cli < acms.sql
    drush updatedb --yes -vvv
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

# Enable Starter or Pubsec Demo if Appropriate
if [[ "$ACMS_JOB" == "starter" ]] && [[ -n "$ACMS_STARTER_DB_ARTIFACT" ]] && [[ -n "$ACMS_STARTER_FILES_ARTIFACT" ]] && [[ -f "$ACMS_STARTER_DB_ARTIFACT" ]] && [[ -f "$ACMS_STARTER_FILES_ARTIFACT" ]]; then
    cd "$ORCA_FIXTURE_DIR"
    echo "Installing Starter From Artifacts"
    tar -x -z -v -f $ACMS_STARTER_FILES_ARTIFACT --directory docroot/sites/default/files
    DB="$ACMS_STARTER_DB_ARTIFACT"
    php docroot/core/scripts/db-tools.php import ${DB}
    drush updatedb --yes
fi

if [[ "$ACMS_JOB" == "pubsec" ]] && [[ "$ACMS_PUBSEC_DB_ARTIFACT" && "$ACMS_PUBSEC_FILES_ARTIFACT" ]] && [[ -f "$ACMS_PUBSEC_DB_ARTIFACT" ]] && [[ -f "$ACMS_PUBSEC_FILES_ARTIFACT" ]]; then
    cd "$ORCA_FIXTURE_DIR"
    echo "Installing PubSec Demo From Artifacts"
    tar -x -z -v -f $ACMS_PUBSEC_FILES_ARTIFACT --directory docroot/sites/default/files
    DB="$ACMS_PUBSEC_DB_ARTIFACT"
    php docroot/core/scripts/db-tools.php import ${DB}
    drush updatedb --yes
fi

# Set the fixture state to reset to between tests.
orca fixture:backup --force
