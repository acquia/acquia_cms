#!/usr/bin/env bash
# Cloud Hook: Acquia CMS install, update and modules toggle.

site="$1"
target_env="$2"

# Check drush version
/var/www/html/$site.$target_env/vendor/bin/drush --version

# Clear cache after copy database operation to avoid conflicts.
/var/www/html/$site.$target_env/vendor/bin/drush cr

# Toggle Modules based on the environment.
/var/www/html/$site.$target_env/vendor/bin/drush acms:toggle:modules
