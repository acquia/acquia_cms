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

if [ "${ACMS_JOB}" != "backstop_tests" ] && [ "${ACMS_JOB}" != "upgrade_modules" ] && [ "${ACMS_JOB}" != "cypress_tests" ]; then
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

  # Store failed backstop test images.
   npm run backstop-starter && error=false || error=true
   if [ "${error}" = "true" ]; then
     if [ "${COPY_BACKSTOP_IMAGES}" == "true" ]; then
       tar -cvzf test_reports.tar.gz ./tests/backstop/bitmaps_test/;
       aws s3 cp --recursive "./tests/backstop/bitmaps_test/" "${AWS_S3_BUCKET_PATH}/backstop/logs/${GITHUB_RUN_ID}"

       # Uncomment below to Generate backstop test images & copy in AWS.
       # ./node_modules/.bin/backstop reference --config=tests/backstop/backstop-settings.js
       # ./node_modules/.bin/backstop approve --config=tests/backstop/backstop-settings.js
       # aws s3 cp --recursive "./tests/backstop/bitmaps_reference/" "${AWS_S3_BUCKET_PATH}/backstop/reference/${GITHUB_RUN_ID}"
     fi
      exit 1
   fi
  # Runs Pa11y.js
  # npm run pa11y-starter
fi

if [ "${ACMS_JOB}" == "cypress_tests" ]; then
  # Install npm dependencies and run JS test suites.
  cd ${ORCA_SUT_DIR}/tests/cypress
  npm install
  orca fixture:run-server &

  # Runs Cypress tests
  npx cypress run
fi
