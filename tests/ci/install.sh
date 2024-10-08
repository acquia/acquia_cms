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

# creates the ORCA fixture, as we do not want to use ORCA's standard fixture.
create_fixture() {
  # Find drupal core version from ORCA_JOB variable.
  CORE_VERSION=$(echo ${ORCA_JOB} | sed -E -e 's/(INTEGRATED_TEST_ON_|INTEGRATED_UPGRADE_TEST_FROM_|ISOLATED_TEST_ON_|INTEGRATED_UPGRADE_TEST_TO_|ISOLATED_UPGRADE_TEST_TO_)//')
  echo "The CORE_VERSION is: ${CORE_VERSION}"
  orca debug:packages ${CORE_VERSION}
  orca fixture:init --force --sut=acquia/acquia_cms --sut-only --core=${CORE_VERSION} --profile=minimal --no-sqlite --no-site-install
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
# We are using composer-plugin mnsami/composer-custom-directory-installer,
# which by default loads libraries in vendor folder but we are expecting
# them to be in libraries folder hence running below command.
composer config --json extra.installer-paths.'docroot/libraries/{$name}' '["swagger-api/swagger-ui","nnnick/chartjs"]' --merge

# Below added to add swagger/chart.js libraries in CI.
# Without this CI is failing.
# @todo remove below workaround to add proper fix.
mkdir ${ORCA_FIXTURE_DIR}/docroot/libraries
curl "https://codeload.github.com/swagger-api/swagger-ui/zip/refs/tags/v3.0.17" -o ${ORCA_FIXTURE_DIR}/docroot/libraries/v3.0.17.zip
unzip ${ORCA_FIXTURE_DIR}/docroot/libraries/v3.0.17.zip
mv swagger-ui-3.0.17 ${ORCA_FIXTURE_DIR}/docroot/libraries/swagger-ui

# Add slide-element library locally
mkdir -p ${ORCA_FIXTURE_DIR}/docroot/libraries/slide-element
curl "https://unpkg.com/slide-element@2.3.1/dist/index.umd.js" -o ${ORCA_FIXTURE_DIR}/docroot/libraries/slide-element/index.umd.js

# Add chartjs library locally
mkdir -p ${ORCA_FIXTURE_DIR}/docroot/libraries/chartjs/dist/
curl "https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" -o ${ORCA_FIXTURE_DIR}/docroot/libraries/chartjs/dist/chart.umd.min.js

# Install acquia_cms only for the Integrated & ExistingSite PHPUnit tests.
if [ -n "${ACMS_JOB}" ]; then
  if [ "${ACMS_JOB}" == "backstop_tests" ] && [ "${CORE_VERSION}" == "LATEST_LTS" ]; then
    composer config --unset repositories.acquia_cms_common
    composer require drupal/acquia_cms_common:3.x-dev -W
  fi
  if [ "${ACMS_JOB}" == "dev_version_test" ]; then
    composer config extra.composer-exit-on-patch-failure "false" --json
    composer config minimum-stability dev
    composer config prefer-stable false
    composer update "drupal/*"
    # composer update "drupal/next:1.0.x-dev" "drupal/acquia_search:3.1.x-dev"
    composer update "drupal/acquia_search:3.1.x-dev"
    composer update "drupal/core*" "acquia/cohesion*" --prefer-stable -W
  fi
  if [ "${ACMS_JOB}" != "dev_version_test" ]; then
    ./vendor/bin/acms site:install --yes --account-pass admin --uri=http://127.0.0.1:8080

    # Enable Acquia CMS DAM module.
    # @todo We should probably move this in acms site:install command.
    drush en acquia_cms_audio acquia_cms_dam sitestudio_config_management --yes --uri=http://127.0.0.1:8080
  fi
fi

# Allow acquia_cms as allowed package dependencies, so that composer scaffolds acquia_cms files.
# This is important for now, otherwise PHPUnit tests: MaintenancePageTest will fail.
# @todo look for alternative way setting maintenance theme template.
composer config --json extra.drupal-scaffold.allowed-packages '["acquia/acquia_cms"]' --merge && composer update --lock

# Enable Starter on full installs if Appropriate.
if [[ "${ACMS_JOB}" == "backstop_tests" ]] || [ "${ACMS_JOB}" == "cypress_tests" ]; then

    echo "Installing Starter Kit"
    drush en acquia_cms_development -y
    drush en acquia_cms_starter -y
    drush cr
fi

# Set the fixture state to reset to between tests.
orca fixture:backup --force
