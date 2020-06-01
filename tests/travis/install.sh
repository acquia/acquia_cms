#!/usr/bin/env bash

# NAME
#     install.sh - Install Travis CI dependencies
#
# SYNOPSIS
#     install.sh
#
# DESCRIPTION
#     Creates the test fixture.

cd "$(dirname "$0")"

# Reuse ORCA's own includes.
source ../../../orca/bin/travis/_includes.sh

# Run ORCA's standard installation script.
../../../orca/bin/travis/install.sh

# If there is no fixture, there's nothing else for us to do.
[[ -d "$ORCA_FIXTURE_DIR" ]] || exit 0

# In order for PHPUnit tests belonging to profile modules to even be
# runnable, the profile's modules need to be symlinked into the
# sites/all/modules directory. This is a long-standing limitation of
# Drupal core (10 year old issue) that shows no signs of being fixed
# any time soon. We do a similar workaround in our composer.json's
# post-install-cmd script.
cd "$ORCA_FIXTURE_DIR/docroot/sites"
mkdir -p ./all/modules
cd ./all/modules
find ../../../profiles/contrib/acquia_cms/modules -maxdepth 1 -mindepth 1 -type d -exec ln -s -f '{}' ';'
# Ensure the symlinks are included in the ORCA fixture snapshot.
git add .

# Set the fixture state to reset to between tests.
orca fixture:backup --force
