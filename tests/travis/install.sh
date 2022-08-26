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

# creates the ORCA fixture, as we do not want to use ORCA's standard fixture.
create_fixture() {
  # Find drupal core version from ORCA_JOB variable.
  CORE_VERSION=$(echo ${ORCA_JOB} | sed -E -e 's/(INTEGRATED_TEST_ON_|INTEGRATED_UPGRADE_TEST_FROM_|ISOLATED_TEST_ON_|INTEGRATED_UPGRADE_TEST_TO_|ISOLATED_UPGRADE_TEST_TO_)//')
  orca debug:packages ${CORE_VERSION}
  orca fixture:init --force --sut=acquia/acquia_cms --sut-only --core=${CORE_VERSION} --dev --profile=minimal --no-sqlite --no-site-install
}

# If running our custom jobs or isolated test jobs, initialize the fixture.
# Otherwise, use Orca's installation script.

if [[ "$ACMS_JOB" == "base" ]] || [[ "$ACMS_JOB" == "starter" ]]; then
  create_fixture
  cat ../../patches/ci-settings.txt >> $ORCA_FIXTURE_DIR/docroot/sites/default/settings.php

# @todo we can remove ACMS_JOB variable from all places.
elif [[ "$ACMS_JOB" == "starter_full" ]] || [[ "${ORCA_JOB}" = ISOLATED* ]] || [[ "${ORCA_JOB}" = INTEGRATED* ]]; then
  create_fixture
  cd $ORCA_FIXTURE_DIR
  ./vendor/bin/acms site:install --yes
else
# Run ORCA's standard installation script.
  ../../../orca/bin/travis/install.sh
fi

printenv | grep ACMS_ | sort

# If there is no fixture, there's nothing else for us to do.
[[ -d "$ORCA_FIXTURE_DIR" ]] || exit 0

cd $ORCA_FIXTURE_DIR

# Rebuild cohesion after install.
#if [[ "$ACMS_JOB" != "base" ]] && [[ "$ACMS_JOB" != "starter" ]] && [[ "$ORCA_JOB" != "LOOSE_DEPRECATED_CODE_SCAN" ]] && [[ "$ORCA_JOB" != "DEPRECATED_CODE_SCAN_W_CONTRIB" ]] && [[ "$ORCA_JOB" != "STRICT_DEPRECATED_CODE_SCAN" ]]; then
#  drush cohesion:rebuild -y
#fi

# Allow acquia_cms as allowed package dependencies, so that composer scaffolds acquia_cms files.
composer config --json extra.drupal-scaffold.allowed-packages '["acquia/acquia_cms"]' --merge

# Allow third party plugins so that they are not blocked when CI jobs run by ORCA.
composer config --no-plugins allow-plugins.dealerdirect/phpcodesniffer-composer-installer true;
composer config --no-plugins allow-plugins.ergebnis/composer-normalize true;

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

  # Workaround to switch profile from acquia_cms to minimal.
  # @todo Remove this after we update tests artifacts, which is created based on release 2.0.x.
  drush sqlq 'UPDATE `config` SET `data` = replace(data, "s:10:\"acquia_cms\"", "s:7:\"minimal\"") where name="core.extension";'
  drush cr

  drush updatedb --cache-clear --yes -vvv
  drush cr
fi

# Use Starter artifacts if appropriate.
if [[ "$ACMS_JOB" == "starter" ]] && [[ -n "$ACMS_STARTER_DB_ARTIFACT" ]] && [[ -n "$ACMS_STARTER_FILES_ARTIFACT" ]] && [[ -f "$ACMS_STARTER_DB_ARTIFACT" ]] && [[ -f "$ACMS_STARTER_FILES_ARTIFACT" ]]; then
  echo "Installing Starter From Artifacts"
  tar -xzf $ACMS_STARTER_FILES_ARTIFACT
  gunzip $ACMS_STARTER_DB_ARTIFACT
  drush sql:cli < $TRAVIS_BUILD_DIR/tests/acms-starter.sql

  # Workaround to switch profile from acquia_cms to minimal.
  # @todo Remove this after we update tests artifacts, which is created based on release 2.0.x.
  drush sqlq 'UPDATE `config` SET `data` = replace(data, "s:10:\"acquia_cms\"", "s:7:\"minimal\"") where name="core.extension";'
  drush cr

  drush updatedb --cache-clear --yes -vvv
fi

# Enable Starter on full installs if Appropriate.
if [[ "$ACMS_JOB" == "starter_full" ]]; then
    echo "Installing Starter Kit"
    drush en acquia_cms_development -y
    drush pmu shield -y
    drush en acquia_cms_starter -y
fi

# Set the fixture state to reset to between tests.
orca fixture:backup --force
