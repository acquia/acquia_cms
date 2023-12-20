#!/bin/bash

cd "$(dirname "$0")"

find ../modules -maxdepth 1 -not -path "*modules"  | while read -r MODULE;
do
  MODULE_NAME="$(echo "${MODULE}" | sed 's/.*modules.//' | tr '[a-z]' '[A-Z]')_IGNORE_PACKAGES"
  IGNORE_PACKAGES=$(printenv $MODULE_NAME)
  IFS=',' ;for IGNORE in `echo "${IGNORE_PACKAGES}"`;
  do
    IGNORE_COMMAND="${IGNORE_COMMAND}--ignore=${IGNORE} ";
  done
  sed '/"drupal\/acquia_cms_.*-dev"/d' ${MODULE}/composer.json
  composer config minimum-stability dev -d ${MODULE}
  composer config prefer-stable true -d ${MODULE}
  composer install -d ${MODULE}
  if [[ ! -z "${IGNORE_COMMAND}" ]]; then
    composer outdated --direct -d ${MODULE} --strict ${IGNORE_COMMAND}
  else
    composer outdated --direct -d ${MODULE} --strict
  fi
done
