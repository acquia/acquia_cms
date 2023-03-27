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

if [ "${ACMS_JOB}" != "backstop_tests" ] && [ "${ACMS_JOB}" != "upgrade_modules" ] && [ "${ACMS_JOB}" != "blt" ]; then
  # Run ORCA's standard script.
  ../../../orca/bin/travis/script.sh
fi

# If there is no fixture, there's nothing else for us to do.
[[ -d "${ORCA_FIXTURE_DIR}" ]] || exit 0

cd ${ORCA_FIXTURE_DIR}
if [ "${ACMS_JOB}" == "backstop_tests" ] || [ "${ACMS_JOB}" == "upgrade_modules" ] || [ "${ACMS_JOB}" == "blt" ]; then
  # Install npm dependencies and run JS test suites.
  cd ${ORCA_SUT_DIR}
  npm install
  orca fixture:run-server &

  # Runs Backstop.js
  npm run backstop-starter
  npm run backstop-starter && error=false || error=true

  # Enable below code to store backstop images on S3 bucket.
  #if [ "${error}" = "true" ]; then
  #  ./node_modules/.bin/backstop reference --config=tests/backstop/backstop-settings.js
  #  ./node_modules/.bin/backstop approve --config=tests/backstop/backstop-settings.js
  #  aws s3 ls "./tests/backstop/bitmaps_reference/" "${AWS_S3_BUCKET_PATH}/bitmaps_reference"
  #  aws s3 cp --recursive "./tests/backstop/bitmaps_reference/" "${AWS_S3_BUCKET_PATH}/backstop/reference/${GITHUB_RUN_ID}"

  # Runs Pa11y.js
  # npm run pa11y-starter
fi
