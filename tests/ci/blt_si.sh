#!/usr/bin/env bash

cd "$(dirname "$0")"

# Reuse ORCA's own includes.
source ../../../orca/bin/ci/_includes.sh

# If there is no fixture, there's nothing else for us to do.
[[ -d "${ORCA_FIXTURE_DIR}" ]] || exit 0

cd ${ORCA_FIXTURE_DIR}

./vendor/bin/drush en acquia_cms_site_studio_cm --yes
./vendor/bin/drush en acquia_config_management --yes
./vendor/bin/drush config:set system.site uuid ${UUID}
./vendor/bin/drush cex --yes
./vendor/bin/drush site:install minimal --yes --uri=http://127.0.0.1:8080
./vendor/bin/drush config:set system.site uuid ${UUID} --yes
./vendor/bin/drush cim --yes
./vendor/bin/drush cex --yes

CONFIG_STATUS=$(./vendor/bin/drush config:status 2>&1 | grep "No differences")
if [ -z "${CONFIG_STATUS}" ]; then
  exit 1
fi
