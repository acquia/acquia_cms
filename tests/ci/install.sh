#!/usr/bin/env bash

# NAME
#     install.sh - Install CI dependencies
#
# SYNOPSIS
#     install.sh
#
# DESCRIPTION
#     Creates the test fixture.

cd "$(dirname "$0")"

# Reuse ORCA's own includes.
source ../../../orca/bin/ci/_includes.sh

# Gets the core version to download.
get_core_version() {
  ORCA_SUPPORTED_DRUPAL_VERSIONS=$(orca debug:core-versions | sed -e '1,3d' | sed '$d' | awk '{ print $2 }')
  CORE_VERSION=$(echo ${ORCA_JOB} | sed -E -e 's/(INTEGRATED_TEST_ON_|INTEGRATED_UPGRADE_TEST_FROM_|ISOLATED_TEST_ON_)//')
  FOUND=FALSE
  while read SUPPORTED_VERSION
  do
    if [ "${SUPPORTED_VERSION}" == "${CORE_VERSION}" ]; then
      FOUND=TRUE
      break
    fi
  done <<< "${ORCA_SUPPORTED_DRUPAL_VERSIONS}"
  if [ "${FOUND}" == "FALSE" ]; then
    CORE_VERSION=CURRENT
  fi
  echo ${CORE_VERSION}
}

# creates the ORCA fixture, as we do not want to use ORCA's standard fixture.
create_fixture() {
  # Find drupal core version from ORCA_JOB variable.
  CORE_VERSION=$(get_core_version)

  echo "The CORE_VERSION is: ${CORE_VERSION}"
  orca debug:packages ${CORE_VERSION}
  orca fixture:init --force --sut=acquia/acquia_cms --sut-only --core=${CORE_VERSION} --dev --profile=minimal --no-sqlite --no-site-install
}

if [ "${JOB_TYPE}" == "static-code-analysis" ]; then
  # Run ORCA's standard installation script.
  ../../../orca/bin/travis/install.sh
else
  create_fixture
  printenv | grep ACMS_ | sort
fi

# If there is no fixture, there's nothing else for us to do.
[[ -d "${ORCA_FIXTURE_DIR}" ]] || exit 0

cd ${ORCA_FIXTURE_DIR}

# Install acquia_cms only for the Integrated & ExistingSite PHPUnit tests.
if [ -n "${ACMS_JOB}" ]; then
  ./vendor/bin/acms site:install --yes
fi

# Allow acquia_cms as allowed package dependencies, so that composer scaffolds acquia_cms files.
# This is important for now, otherwise PHPUnit tests: MaintenancePageTest will fail.
# @todo look for alternative way setting maintenance theme template.
composer config --json extra.drupal-scaffold.allowed-packages '["acquia/acquia_cms"]' --merge && composer update --lock

# Enable Starter on full installs if Appropriate.
if [[ "${ACMS_JOB}" == "backstop_tests" ]]; then

    echo "Installing Starter Kit"
    drush en acquia_cms_development -y
    drush pmu shield -y
    drush en acquia_cms_starter -y
fi

# Set the fixture state to reset to between tests.
orca fixture:backup --force
