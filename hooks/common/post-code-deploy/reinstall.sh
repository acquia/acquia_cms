#!/bin/sh
#
# Cloud Hook: Reinstall Acquia CMS
#
# Run `drush site-install acquia_cms` in the target environment.

which drush
drush --version

site="$1"
target_env="$2"

# Fresh install of Acquia CMS. We need to clear cache first in case memcache is
# enabled, else there will be a collision on site install.
/usr/local/bin/drush9 @$site.$target_env cr
/usr/local/bin/drush9 @$site.$target_env site-install acquia_cms --account-pass=admin --yes --account-mail=no-reply@acquia.com --site-mail=no-reply@acquia.com
/usr/local/bin/drush9 @$site.$target_env pm-enable acquia_cms_development --yes
