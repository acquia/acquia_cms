#!/usr/bin/env bash

# NAME
#     deploy.sh - Deploy Code on Drupal.org
#
# SYNOPSIS
#     deploy.sh
#
# DESCRIPTION
#     Deploy the acquia_cms module code on drupal.org.

cd "$(dirname "$0")"

exit_script() {
  if [ $1 = "false" ]; then
    exit 1
  fi
}
declare -a acms_modules=("acquia_cms_article" "acquia_cms_audio" "acquia_cms_component" "acquia_cms_document" "acquia_cms_event" "acquia_cms_headless" "acquia_cms_image" "acquia_cms_page" "acquia_cms_person" "acquia_cms_place" "acquia_cms_search" "acquia_cms_site_studio" "acquia_cms_starter" "acquia_cms_toolbar" "acquia_cms_video")

for acms_module in "${acms_modules[@]}"
do
  ./acms-split.sh --branch=1.x --push=drupal --module=${acms_module} && success=true || success=false
  exit_script ${success}
done

./acms-split.sh --branch=2.x --push=drupal --module=acquia_cms_tour && success=true || success=false
exit_script ${success}

# We need to do workaround to push code on drupal.org for acquia_cms_common module
# As we've made a release for 3.x branch and we can't force push on release branch. :/
./acms-split.sh --branch=develop --push=drupal --module=acquia_cms_common && success=true || success=false
exit_script ${success}
mkdir ../clone
git clone git@git.drupal.org:project/acquia_cms_common.git --branch=develop ../clone/acquia_cms_common && cd ../clone/acquia_cms_common
git pull origin 3.x --rebase
git push origin develop:3.x

# Move back to the root directory.
cd -

# We need to do workaround to push code on drupal.org for acquia_cms_dam module
# As we've made a release for 1.x branch and we can't force push on release branch. :/
./acms-split.sh --branch=develop --push=drupal --module=acquia_cms_dam && success=true || success=false
exit_script ${success}
git clone git@git.drupal.org:project/acquia_cms_dam.git --branch=develop ../clone/acquia_cms_dam && cd ../clone/acquia_cms_dam
git pull origin 1.x --rebase
git push origin develop:1.x

# Move back to the root directory.
cd -

# We need to do workaround to push code on drupal.org for sitestudio_config_management module
# As we've made a release for 1.x branch and we can't force push on release branch. :/
./acms-split.sh --branch=develop --push=drupal --module=sitestudio_config_management && success=true || success=false
exit_script ${success}
git clone git@git.drupal.org:project/sitestudio_config_management.git --branch=develop ../clone/sitestudio_config_management && cd ../clone/sitestudio_config_management
git pull origin 1.x --rebase
git push origin develop:1.x
