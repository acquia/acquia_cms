#!/bin/sh
#
# Cloud Hook: Reinstall Acquia CMS
#
# Run `drush site-install acquia_cms` in the target environment.

which drush
drush --version

site="$1"
target_env="$2"

# Fresh install of Acquia CMS.
/usr/local/bin/drush9 @$site.$target_env site-install acquia_cms --account-pass=admin --yes
/usr/local/bin/drush9 @$site.$target_env en acquia_cms_example --yes
