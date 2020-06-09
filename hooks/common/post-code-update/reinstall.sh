#!/usr/bin/env sh
#
# Acquia Cloud hook to reinstall Acquia CMS.
#
# Run `drush site:install acquia_cms` in the target environment.
# To import Cohesion templates at install time, ensure that the
# COHESION_API_KEY and COHESION_ORG_KEY environment variables
# are defined.

drush9 @$1.$2 site:install acquia_cms --account-pass drupalwizard --yes
