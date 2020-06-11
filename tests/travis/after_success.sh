#!/usr/bin/env bash

# NAME
#     after_success.sh - Runs after a successful build.
#
# SYNOPSIS
#     after_success.sh
#
# DESCRIPTION
#     Packages the ORCA fixture as a build artifact and pushes
#     it to Acquia Cloud.

cd "$(dirname "$0")"

# Reuse ORCA's own includes.
source ../../../orca/bin/travis/_includes.sh

# If there is no fixture, there's nothing else for us to do.
[[ -d "$ORCA_FIXTURE_DIR" ]] || exit 0

# Ensure that we are on the develop branch, with no active pull request.
# if [ $TRAVIS_BRANCH != "develop" ] || [ $TRAVIS_PULL_REQUEST != "false" ]; then
#   exit 0
# fi

# Restore the fixture to a clean state.
orca fixture:reset --force

PROFILE_DIR="$ORCA_FIXTURE_DIR/docroot/profiles/contrib/acquia_cms"
SITE_DIR="$ORCA_FIXTURE_DIR/docroot/sites/default"

# Since we will be pushing to the remote 'develop' branch, create a new
# local 'develop' branch.
cd $ORCA_FIXTURE_DIR
git checkout -b develop master

# The BLT template project used by ORCA ignores many files that we need
# to include in our build artifact.
git rm .gitignore docroot/.gitignore
# We don't want any tests or git repositories in the artifact.
find . -type d -name 'tests' -prune -exec rm -r -f '{}' ';'
find . -mindepth 2 -type d -name '.git' -prune -exec rm -r -f '{}' ';'

# Delete the SQLite database used by ORCA.
git rm db.sqlite
# install.sh creates symlinks the profile modules into sites/all/modules. We
# don't need or want these in the artifact.
rm -r -f docroot/sites/all
# Ensure the site directory is writable so we can clean it.
chmod +w $SITE_DIR
git clean -d -f $SITE_DIR

# Replace the Cloud hooks from the BLT template project with our own.
rm -r -f hooks
cp -R $TRAVIS_BUILD_DIR/hooks .

# Replace the symlinked SUT with a physical copy.
if [ -L $PROFILE_DIR ]; then
  rm $PROFILE_DIR
  mkdir -p $PROFILE_DIR
  composer archive --working-dir $TRAVIS_BUILD_DIR --format tar --dir $HOME --file acquia_cms
  tar -x -f $HOME/acquia_cms.tar -C $PROFILE_DIR
  rm $HOME/acquia_cms.tar
fi

# Replace settings.php with a clean, Cloud-ready version.
SETTINGS_PHP="$SITE_DIR/settings.php"
chmod +w $SETTINGS_PHP
cat $SITE_DIR/default.settings.php | sed '$ a require_once "/var/www/site-php/orionacms/orionacms-settings.inc";' > $SETTINGS_PHP
echo '$config_directories = ["sync" => "../config"];' >> $SETTINGS_PHP

# We have our build artifact; commit all the things!
git add .
git commit -m "Build artifact" --quiet

# Add the Acquia Cloud remote and force push to it.
SSH_KEY="$TRAVIS_BUILD_DIR/tests/travis/deploy.pem"
GIT_HOST=svn-21939.prod.hosting.acquia.com

eval "$(ssh-agent -s)"
chmod 600 $SSH_KEY
ssh-add $SSH_KEY
ssh-keyscan -H $GIT_HOST >> ~/.ssh/known_hosts
git remote add deploy orionacms@$GIT_HOST:orionacms.git
git push deploy -u develop --force
