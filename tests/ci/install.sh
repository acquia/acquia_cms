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
composer config --json extra.installer-paths.'docroot/libraries/{$name}' '["swagger-api/swagger-ui"]' --merge

# Add the chart.js repository in orca fixture directory.
CHAR_JS_REPOSITORY=$(composer config repositories.chart.js -d ${ORCA_SUT_DIR})
composer config repositories.chart.js "${CHAR_JS_REPOSITORY}"

# Allow acquia_cms as allowed package dependencies, so that composer scaffolds acquia_cms files.
# This is important for now, otherwise PHPUnit tests: MaintenancePageTest will fail.
# @todo look for alternative way setting maintenance theme template.
composer config --json extra.drupal-scaffold.allowed-packages '["acquia/acquia_cms", "drupal/acquia_cms_site_studio"]' --merge

# The acquia/drupal-recommended-project adds the drupal scaffold for default.settings.php file.
# We need to remove it as drupal core already add the same file.
# @todo We should remove this from acquia/drupal-recommended-project and then we can remove below code from here.
FILE_MAPPING=$(composer config --json extra.drupal-scaffold.file-mapping | sed 's/\(,"\[web-root\]\/sites\/default\/default\.settings\.php\":.*\}\)}/}/')
composer config --json extra.drupal-scaffold.file-mapping "${FILE_MAPPING}"

# Run composer install command to download libraries and apply scaffolding.
composer install && composer update --lock

# Install acquia_cms only for the Integrated & ExistingSite PHPUnit tests.
if [ -n "${ACMS_JOB}" ]; then
  ./vendor/bin/acms site:install --yes --uri=http://127.0.0.1:8080
  # Enable Acquia CMS DAM module.
  # @todo We should probably move this in acms site:install command.
  drush en acquia_cms_dam --yes --uri=http://127.0.0.1:8080
fi

# Enable Starter on full installs if Appropriate.
if [[ "${ACMS_JOB}" == "backstop_tests" ]]; then

    echo "Installing Starter Kit"
    drush en acquia_cms_development -y
    drush en acquia_cms_starter -y
    drush cr
fi

# Set the fixture state to reset to between tests.
orca fixture:backup --force
