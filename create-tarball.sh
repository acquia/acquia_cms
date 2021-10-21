#!/bin/bash

# Usage run create-tarball.sh and enter the current version to generate a tarball.

read -p "Enter the ACMS version (ex: 1.3.0): " VERSION
ARCHIVE=acms-${VERSION}

# Creates a project using the legacy drupal project layout with the vendor folder
# inside the root directory.
composer create-project --stability beta --no-install drupal/legacy-project:~9.2.0 $ARCHIVE
composer dump-autoload
composer configure-tarball $ARCHIVE

cd $ARCHIVE
composer config minimum-stability dev
composer config prefer-stable true
composer config repositories.assets composer https://asset-packagist.org

composer config repositories.acms vcs git@github.com:acquia/acquia_cms.git
composer require --no-update "ext-dom:*" "acquia/acquia_cms:${VERSION}" cweagans/composer-patches
composer update

# Add the version number to the info file.
echo "version: ${VERSION}" >> ./profiles/contrib/acquia_cms/acquia_cms.info.yml

# Wrap it all up in a nice compressed tarball.
cd ..
tar --exclude='.DS_Store' --exclude='._*' -c -z -f $ARCHIVE.tar.gz $ARCHIVE

# Clean up.
rm -r -f $ARCHIVE
