#!/bin/bash

# Create tarball with acms version specific.
if [ $1 ] ; then
  ARCHIVE=acms-$1
# Create tarball with latest version of acquia CMS
else
  ARCHIVE=acms
fi

composer create-project --no-install drupal/legacy-project $ARCHIVE
composer dump-autoload
composer configure-tarball $ARCHIVE

cd $ARCHIVE
if [ $1 ] ; then
  composer require --no-update "ext-dom:*" "acquia/acquia_cms:$1" cweagans/composer-patches
else
  composer require --no-update "ext-dom:*" "acquia/acquia_cms" cweagans/composer-patches
fi
composer config minimum-stability dev
# Allow scaffolding from acquia_cms
composer config --json --merge extra.drupal-scaffold.allowed-packages '["acquia/acquia_cms"]'
composer config prefer-stable true
composer update

# Wrap it all up in a nice compressed tarball.
cd ..
tar --exclude='.DS_Store' --exclude='._*' -c -z -f $ARCHIVE.tar.gz $ARCHIVE

# Clean up.
rm -r -f $ARCHIVE
