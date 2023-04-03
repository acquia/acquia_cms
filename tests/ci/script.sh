#!/usr/bin/env bash

# NAME
#     script.sh - Run ORCA tests.
#
# SYNOPSIS
#     script.sh
#
# DESCRIPTION
#     Runs static code analysis and automated tests.

cd "$(dirname "$0")"

# Reuse ORCA's own includes.
source ../../../orca/bin/travis/_includes.sh

if [ "${ACMS_JOB}" != "backstop_tests" ] && [ "${ACMS_JOB}" != "upgrade_modules" ]; then
  # Run ORCA's standard script.
  ../../../orca/bin/travis/script.sh
fi

# If there is no fixture, there's nothing else for us to do.
[[ -d "${ORCA_FIXTURE_DIR}" ]] || exit 0

cd ${ORCA_FIXTURE_DIR}
if [ "${ACMS_JOB}" == "backstop_tests" ] || [ "${ACMS_JOB}" == "upgrade_modules" ]; then
  # Install npm dependencies and run JS test suites.
  cd ${ORCA_SUT_DIR}
  npm install
  orca fixture:run-server &

  # Runs Backstop.js
  npm run backstop-starter
  # Runs Pa11y.js
  # npm run pa11y-starter
fi
