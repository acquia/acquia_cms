#!/usr/bin/env bash

cd "$(dirname "$0")"

GREEN="\033[0;32m"
NOCOLOR="\033[0m"

# Reuse ORCA's own includes.
source ../../../orca/bin/ci/_includes.sh

# We need to revert all the changes that we've done in SUT directory.
cd ${ORCA_SUT_DIR}

# Revert the commit that we've added earlier.
# @see ./tests/ci/before_install.blt.sh
git revert ${DRUPAL_ORG_CHANGES} --no-edit &> log.txt

# If git revert was un-successful, display error and exit with status code 1.
if [ $? -ne 0 ]; then
  cat log.txt
  exit 1
fi

# We loop through all path repositories we've in SUT directory,
# change the relative path of all all path repositories to the absolute path
# and we add all this path repositories in ORCA fixture directory.
composer config repositories --list | grep "\[repositories.*.type.*path" | sed 's/\[repositories\.\(.*\)\.type.*/\1/g' | while read -r repo_name;
do
  SUT_REPOSITORIES=$(composer config repositories.${repo_name} | sed 's@\"url"\:".\/@'"\"url\":\"$ORCA_SUT_DIR/"'@')
  composer config repositories.${repo_name} "${SUT_REPOSITORIES}" -d ${ORCA_FIXTURE_DIR}
done

cd ${ORCA_FIXTURE_DIR}

echo -e "${GREEN}-----------------------------------------------Before-----------------------------------------------${NOCOLOR}"
composer show "drupal/*" | awk -F ' ' '{print $1,$2}' | column -t -s' '
composer show "acquia/*" | awk -F ' ' '{print $1,$2}' | column -t -s' '
echo -e "${GREEN}----------------------------------------------------------------------------------------------------${NOCOLOR}"

# Remove composer.lock file and re-download all modules.
# This time it would download acquia_cms modules from your branch.
rm composer.lock && composer install && composer update --lock

echo -e "${GREEN}-----------------------------------------------After-----------------------------------------------${NOCOLOR}"
composer show "drupal/*" | awk -F ' ' '{print $1,$2}' | column -t -s' '
composer show "acquia/*" | awk -F ' ' '{print $1,$2}' | column -t -s' '
echo -e "${GREEN}----------------------------------------------------------------------------------------------------${NOCOLOR}"

# Clear caches & Run all update hooks.
drush cr && drush updb --yes

# If update-hook was un-successful, exit with status code 1.
if [ $? -ne 0 ]; then
  exit 1
fi
