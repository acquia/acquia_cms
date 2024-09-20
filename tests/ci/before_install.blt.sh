#!/usr/bin/env bash

cd "$(dirname "$0")"

# Reuse ORCA's own includes.
source ../../../orca/bin/ci/_includes.sh

../../../orca/bin/ci/before_install.sh

cd ${ORCA_SUT_DIR}

# We loop through all path repositories that are in the SUT
# and we remove all of these path repositories as we need to
# download all modules from drupal.org (instead from path)
composer config repositories --list | grep "\[repositories.*.type.*path" | sed 's/\[repositories\.\(.*\)\.type.*/\1/g' | while read -r repo_name;
do
    composer config repositories.${repo_name} --unset
done

# After all path repositories are removed, we update composer.json
# with the latest release of each acquia_cms modules.
composer show "drupal/acquia_cms*" --direct --locked --name-only | while read -r module_dependency;
do
  composer require "${module_dependency}" --no-update --no-install
done

# Remove the modules folder. This is very important, otherwise
# Drupal will see all acquia_cms modules from two folders i.e
#  1. ./docroot/modules/contrib/acquia_cms/modules/acquia_cms_common
#  2. ./docroot/modules/contrib/acquia_cms_common
# In below, we are removing #1, as we want to Install site from acquia_cms modules
# that are at path #2 i.e modules from drupal.org.
rm -fr modules

git add . &> log.txt

# If git add command was un-successful, display error and exit with status code 1.
if [ $? -ne 0 ]; then
  cat log.txt
  exit 1
fi

git commit -m "ACMS-000: Update dependencies to download modules from drupal.org" &>> log.txt

# If git commit command was un-successful, display error and exit with status code 1.
if [ $? -ne 0 ]; then
  cat log.txt
  exit 1
fi

rm log.txt

# Get the last commit hash and add it in environment variable,
# as later we want to revert this commit.
# @see ./tests/ci/run_update_hooks.sh
DRUPAL_ORG_CHANGES=$(git rev-parse HEAD)
echo ${DRUPAL_ORG_CHANGES}
echo "DRUPAL_ORG_CHANGES=${DRUPAL_ORG_CHANGES}" >> ${GITHUB_ENV}
