#!/usr/bin/env bash

# These commands shouldn't run in acquia pipelines.
if [ $PIPELINE_ENV != "true" ]; then
  export PROFILE_DIR=./docroot/profiles/acquia_cms
  export MODULES_DIR=./docroot/sites/all
  mkdir -p $PROFILE_DIR $MODULES_DIR
  find $PWD -name 'acquia_cms.*' -type f -maxdepth 1 -exec ln -s -f '{}' $PROFILE_DIR ';'
  ln -s -f $PWD/config $PROFILE_DIR
  ln -s -f $PWD/misc $PROFILE_DIR
  ln -s -f $PWD/modules $MODULES_DIR
  ln -s -f $PWD/modules $PROFILE_DIR
  ln -s -f $PWD/themes $PROFILE_DIR
  ln -s -f $PWD/src $PROFILE_DIR
  ln -s -f $PWD/tests $PROFILE_DIR
  find ./docroot/modules -type d -name tests -prune -exec rm -r -f '{}' ';'
  cp -f phpunit.xml ./docroot/core
  composer install:frontend
  composer build:frontend
fi
