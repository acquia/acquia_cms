#!/bin/bash

ARCHIVE=acms-$1

composer create-project --stability dev --no-install --repository '{"type": "vcs","url": "git@github.com:acquia/acquia-cms-project.git"}' acquia/acquia-cms-project $ARCHIVE
composer dump-autoload
composer configure-tarball $ARCHIVE

cd $ARCHIVE
composer config minimum-stability dev
composer config prefer-stable true
composer config repositories.assets composer https://asset-packagist.org

composer config repositories.acms vcs git@github.com:acquia/acquia_cms.git
composer remove --no-update composer/installers
composer require --no-update "ext-dom:*" "acquia/acquia_cms:~1.0.0-beta8" cweagans/composer-patches
composer update

# Add the version number to the info file.
echo "version: $1" >> ./docroot/profiles/contrib/acquia_cms/acquia_cms.info.yml

# Wrap it all up in a nice compressed tarball.
cd ..
tar --exclude='.DS_Store' --exclude='._*' -c -z -f $ARCHIVE.tar.gz $ARCHIVE

# Clean up.
rm -r -f $ARCHIVE
