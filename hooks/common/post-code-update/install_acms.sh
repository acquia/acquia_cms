#!/usr/bin/env bash
# Cloud Hook: Acquia CMS install, update and modules toggle.

site="$1"
target_env="$2"

# Check drush version
/var/www/html/$site.$target_env/vendor/bin/drush --version

# Fresh install of Acquia CMS. We need to clear cache first in case memcache is
# enabled, else there will be a collision on site install.
/var/www/html/$site.$target_env/vendor/bin/drush cr

# Do not re-install site if env $SITE_REINSTALL is set to false.
if [ "$SITE_REINSTALL" = "false" ]; then
    /var/www/html/$site.$target_env/vendor/bin/drush updatedb --no-interaction
else
    # Install site with given starter kit.
    if [ "$STARTER_KIT" ]; then
      /var/www/html/$site.$target_env/vendor/bin/acms site-install --uri $STARTER_KIT -n --account-pass=admin --yes --account-mail=no-reply@example.com --site-mail=no-reply@example.com -v

    # Install site with default starter kit i.e low code.
    else
      # Install Acquia CMS.
      /var/www/html/$site.$target_env/vendor/bin/acms site-install --uri default --account-pass=admin --yes --account-mail=no-reply@example.com --site-mail=no-reply@example.com -v

      # Acquia CMS development.
      echo "Enabling Acquia CMS development module in $target_env"
      /var/www/html/$site.$target_env/vendor/bin/drush pm-enable acquia_cms_development --yes

      # Acquia CMS starter.
      if [ "$ENABLE_STARTER" = "true" ]; then
        echo "Enabling Acquia CMS starter module in $target_env"
        /var/www/html/$site.$target_env/vendor/bin/drush pm-enable acquia_cms_starter --yes
      fi
    fi
fi
