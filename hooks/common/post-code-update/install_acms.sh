#!/usr/bin/env bash
#
# Factory Hook: db-update
#
# Run cohesion commands after executing

SITEGROUP="$1"
ENVIRONMENT="$2"
DB_ROLE="$3"
DOMAIN="$4"
ATTEMPT="$5"

echo "Sitegroup: $SITEGROUP"
echo "Environment: $ENVIRONMENT"
echo "DB role: $DB_ROLE"
echo "Domain: $DOMAIN"
echo "Attempt: $ATTEMPT"

# Drush executable:
drush="/mnt/www/html/$SITEGROUP.$ENVIRONMENT/vendor/bin/drush"

which drush
drush --version

# Fresh install of Acquia CMS. We need to clear cache first in case memcache is
# enabled, else there will be a collision on site install.
drush @SITEGROUP.$ENVIRONMENT cr

# Only run update hooks on ode4. ode4 is used to test update path.
if [ "$ENVIRONMENT" = "ode5" ]; then
    drush @SITEGROUP.$ENVIRONMENT updatedb --no-interaction
# Install Acquia CMS.
else
    /var/www/html/$SITEGROUP.$ENVIRONMENT/vendor/bin/acms site-install minimal --account-pass=admin --yes --account-mail=no-reply@example.com --site-mail=no-reply@example.com
fi

# Toggle Modules based on the environment.
drush @SITEGROUP.$ENVIRONMENT pm-enable acquia_cms_development --yes

# Enable development related modules. This is for ease of development for core
# Acquia CMS development.
echo "Enabling Acquia CMS Starter in $ENVIRONMENT"
case $ENVIRONMENT in
  ode1)
    drush @SITEGROUP.$ENVIRONMENT pm-enable acquia_cms_starter --yes
    ;;

  ode3)
    drush @SITEGROUP.$ENVIRONMENT pm-enable acquia_cms_starter --yes
    ;;

  stage)
    drush @SITEGROUP.$ENVIRONMENT pm-enable acquia_cms_starter --yes
    ;;
esac
