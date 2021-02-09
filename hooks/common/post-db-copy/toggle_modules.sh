#!/bin/sh
# Cloud Hook: Acquia CMS install,update and modules toggle.

which drush
drush --version

site="$1"
target_env="$2"

# Clear cache after copy database operation to avoid conflicts.
/usr/local/bin/drush9 @$site.$target_env cr
# Toggle Modules based on the environment.
/usr/local/bin/drush9 @$site.$target_env acms:toggle:modules
