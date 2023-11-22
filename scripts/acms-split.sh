#!/bin/bash

cd "$(dirname "$0")"

# Color codes definition.
GREEN="\033[0;32m"
YELLOW="\033[0;33m"
NOCOLOR="\033[0m"
RED="\033[0;31m"
RED_BG="\033[41m"
WHITE="\033[1;37m"

declare -a acquia_cms_modules=("acquia_cms_article" "acquia_cms_audio" "acquia_cms_common" "acquia_cms_component" "acquia_cms_dam" "acquia_cms_document" "acquia_cms_event" "acquia_cms_headless" "acquia_cms_image" "acquia_cms_page" "acquia_cms_person" "acquia_cms_place" "acquia_cms_search" "acquia_cms_site_studio" "acquia_cms_starter" "acquia_cms_toolbar" "acquia_cms_tour" "acquia_cms_video" "sitestudio_config_management")

# Displays the command help text.
display_help() {
    echo -e "Usage: $0 --{option}={value}"; echo>&2
    echo -e "${YELLOW}Examples:${NOCOLOR}"
    echo -e "  $0 --branch=develop \t\t  Pushes the code to \`develop\` branch on all sub-modules repository."
    echo -e "  $0 --tag=v1.2.6 \t\t\t  Pushes the code to \`v1.2.6\` tag on all sub-modules repository."
    echo -e "  $0 --tag=v1.2.6 --delete \t  Deletes the tag \`v1.2.6\` and then create tag on all sub-modules repository."
    echo -e "  $0 --branch=develop --force \t  Force pushes the code to \`develop\` branch on all sub-modules repository."
    echo -e "  $0 --branch=develop --dry-run \t  Dry run the git commands to check for any errors."
    echo -e "  $0 --module=acquia_cms_common \t  Pushes the code to \`acquia_cms_common\` module."
    echo -e "  $0 --push=drupal --dry-run \t  Dry run to push the code to drupal repositories."
    echo -e "  $0 --push=acquia,drupal --dry-run  Dry run to push the code to both drupal & acquia repositories."
    echo
    echo -e "${YELLOW}Options:${NOCOLOR}"
    echo -e "  --branch[=BRANCH] \t Pushes the code to given remote branch."
    echo -e "  --tag[=TAG] \t\t Pushes the code to given remote tag."
    echo -e "  --force \t\t Force push the code to given branch/tag."
    echo -e "  --delete \t\t Deletes the given branch/tag."
    echo -e "  --dry-run \t\t Dry run all git commands to simulate code push."
    echo -e "  --push[=REPO] \t Pushes the code to given git repo. Default value: ${GREEN}acquia${NOCOLOR}."
    echo
    exit 1
}

# Displays non empty option value error
display_value_error() {
  echo -e "${RED_BG}${WHITE}ERROR: Option: $1 requires a non-empty option value.${NOCOLOR}\n"
  display_help
}

# Displays incorrect value error
display_incorrect_value_error() {
  echo -e "${RED_BG}${WHITE}ERROR: Option --push can only accept acquia or drupal (or both) as remote values.${NOCOLOR}\n"
  display_help
}

# Parse all options given to command.
while :; do
    case $1 in
        --branch)
          if [[ $2 == -* ]]; then
            display_value_error $1
          fi
          if [ $2 ]; then
                BRANCH=$2
                shift
            else
             display_value_error $1
            fi
            ;;
        --tag)
          if [[ $2 == -* ]]; then
            display_value_error $1
          fi
          if [ $2 ]; then
                TAG=$2
                shift
            else
              display_value_error $1
            fi
            ;;
        --module)
          if [[ $2 == -* ]]; then
            display_value_error $1
          fi
          if [ $2 ]; then
                MODULE=$2
                shift
            else
              display_value_error $1
            fi
            ;;
        --branch=|--tag=|--module=) # Handle the case of an empty
           display_value_error $1
            ;;
        --branch=?*)
            BRANCH=${1#*=} # Delete everything up to "=" and assign the remainder.
            ;;
        --tag=?*)
            TAG=${1#*=} # Delete everything up to "=" and assign the remainder.
            ;;
        --module=?*)
            MODULE=${1#*=} # Delete everything up to "=" and assign the remainder.
            ;;
        --dry-run)
          DRY_RUN="--dry-run"
          ;;
        --force)
          FORCE="--force"
          ;;
        --delete)
          DELETE=1
          ;;
        --push)
          if [[ $2 == -* ]]; then
            display_value_error $1
          fi
          if [ $2 ]; then
                PUSH=$2
                shift
            else
             display_value_error $1
            fi
            ;;
        --push=?*)
          PUSH=${1#*=} # Delete everything up to "=" and assign the remainder.
          ;;
        --) # End of all options.
            shift
            break;;
        -?*)
            echo -e "${RED_BG}${WHITE}ERROR: Unknown option: $1${NOCOLOR}"
            echo
            display_help ;;
        *) # Default case: No more options, so break out of the loop.
            break
    esac
    shift
done

if [ -z "$BRANCH" -a -z "$TAG" ]; then
  echo -e "${RED_BG}${WHITE}ERROR: Pass at-least one of the option i.e --branch or --tag.${NOCOLOR}\n"
  display_help
fi

OPTION=$(echo $PUSH | sed "s/,\s*/ /g" | xargs)
if [[ ! -z $OPTION && ! $OPTION =~ ^(acquia|acquia drupal|drupal acquia|drupal)$ ]]; then
  display_incorrect_value_error
fi

# Split method to run the commands for splitting/updating a split and pushing,
# any new commits.
# $1 is path of module, $2 is name of remote branch for the split.
function split() {
    if [[ ! -z "$BRANCH" ]]; then
      DEST="refs/heads/$BRANCH"
    fi

    if [[ ! -z "$TAG" ]]; then
      DEST="refs/tags/$TAG"
    fi

    SHA1=`./splitsh-lite --prefix=$1 --path=../ --target=${2}/${DEST}`

    if [ ! -z "$DELETE"]; then
      if [ -z "$BRANCH" ]; then
        echo -e "${YELLOW}Deleting Branch: $BRANCH of $2.${NOCOLOR}\n"
        git push $2 :$BRANCH $DRY_RUN $FORCE
      elif [ -z "$TAG" ]; then
        echo -e "${YELLOW}Deleting Tag: $TAG of $2.${NOCOLOR}\n"
        git push $2 :$TAG $DRY_RUN $FORCE
      fi
    else
      if [ ! -z "${SHA1}" ]; then
        module=$(echo "$2" | sed 's/drupal_//')
        echo -e "Pushing code on '${PUSH}' => $module : $SHA1 : $DEST $DRY_RUN $FORCE\n"
        git push $2 "$SHA1:$DEST" $DRY_RUN $FORCE
      else
        echo -e "${RED_BG}ERROR: Invalid SHA1. Please contact administrator.${NOCOLOR}\n"
        exit 1
      fi
    fi

    if [ $? -ne 0 ]; then
      echo -e "\n${RED_BG}ERROR: Failed pushing code.${NOCOLOR}\n"
      echo -e "${YELLOW}WARN: Try running command with --force instead.${NOCOLOR}\n"
      display_help
      exit 1
    fi
}

# Remote method, to add git remote for destination repository.
# $1 is sub-module repo name and $2 is remote branch name.
function remote() {
    REPO_URL=`git ls-remote --get-url $1`
    if [ "$REPO_URL" = "$1" ]; then
      echo -e "Adding remote: ${GREEN}$1${NOCOLOR}."
      git remote add $1 $2
    else
      echo -e "Remote already exist for: ${YELLOW}$1${NOCOLOR}."
    fi
}

echo -e "Script Running on ${GREEN}${OSTYPE}${NOCOLOR}"

# Sample usage: ./acms-split.sh
# Commit will be traversed/updated from source to destination repository.

# Dynamically setting current branch to checked out branch.
# For example if this script is run on branch, test then,
# commit will traverse from test branch of source repo to,
# test branch of destination repo.
CURRENT_BRANCH=`git rev-parse --abbrev-ref HEAD`

echo -e "Script running for split on branch: ${GREEN}$CURRENT_BRANCH${NOCOLOR}"

# Pull current branch.
#git pull origin $CURRENT_BRANCH

# Function to add Drupal git remote.
function add_drupal_remote() {
  if [[ ! -z ${MODULE} ]]; then
    echo "drupal_${MODULE} : git@git.drupal.org:project/${MODULE}.git"
    remote drupal_${MODULE} git@git.drupal.org:project/${MODULE}.git
    split_drupal_repo
    exit 0
  fi
  # Adding remote for drupal git branches.
  remote drupal_acquia_cms_article git@git.drupal.org:project/acquia_cms_article.git
  remote drupal_acquia_cms_audio git@git.drupal.org:project/acquia_cms_audio.git
  remote drupal_acquia_cms_common git@git.drupal.org:project/acquia_cms_common.git
  remote drupal_acquia_cms_component git@git.drupal.org:project/acquia_cms_component.git
  remote drupal_acquia_cms_dam git@git.drupal.org:project/acquia_cms_dam.git
  remote drupal_acquia_cms_document git@git.drupal.org:project/acquia_cms_document.git
  remote drupal_acquia_cms_event git@git.drupal.org:project/acquia_cms_event.git
  remote drupal_acquia_cms_headless git@git.drupal.org:project/acquia_cms_headless.git
  remote drupal_acquia_cms_image git@git.drupal.org:project/acquia_cms_image.git
  remote drupal_acquia_cms_page git@git.drupal.org:project/acquia_cms_page.git
  remote drupal_acquia_cms_person git@git.drupal.org:project/acquia_cms_person.git
  remote drupal_acquia_cms_place git@git.drupal.org:project/acquia_cms_place.git
  remote drupal_acquia_cms_search git@git.drupal.org:project/acquia_cms_search.git
  remote drupal_acquia_cms_site_studio git@git.drupal.org:project/acquia_cms_site_studio.git
  remote drupal_acquia_cms_starter git@git.drupal.org:project/acquia_cms_starter.git
  remote drupal_acquia_cms_toolbar git@git.drupal.org:project/acquia_cms_toolbar.git
  remote drupal_acquia_cms_tour git@git.drupal.org:project/acquia_cms_tour.git
  remote drupal_acquia_cms_video git@git.drupal.org:project/acquia_cms_video.git
  remote sitestudio_config_management git@git.drupal.org:project/sitestudio_config_management.git

  # Call the split function to push the code.
  split_drupal_repo
}

function split_drupal_repo() {
  if [[ ! -z ${MODULE} ]]; then
    split "modules/${MODULE}" drupal_${MODULE}
    exit 0
  fi
  # Calling split method for mapping drupal remote branches to splits.
  split 'modules/acquia_cms_article' drupal_acquia_cms_article
  split 'modules/acquia_cms_audio' drupal_acquia_cms_audio
  split 'modules/acquia_cms_common' drupal_acquia_cms_common
  split 'modules/acquia_cms_component' drupal_acquia_cms_component
  split 'modules/acquia_cms_dam' drupal_acquia_cms_dam
  split 'modules/acquia_cms_document' drupal_acquia_cms_document
  split 'modules/acquia_cms_event' drupal_acquia_cms_event
  split 'modules/acquia_cms_headless' drupal_acquia_cms_headless
  split 'modules/acquia_cms_image' drupal_acquia_cms_image
  split 'modules/acquia_cms_page' drupal_acquia_cms_page
  split 'modules/acquia_cms_person' drupal_acquia_cms_person
  split 'modules/acquia_cms_place' drupal_acquia_cms_place
  split 'modules/acquia_cms_search' drupal_acquia_cms_search
  split 'modules/acquia_cms_site_studio' drupal_acquia_cms_site_studio
  split 'modules/acquia_cms_starter' drupal_acquia_cms_starter
  split 'modules/acquia_cms_toolbar' drupal_acquia_cms_toolbar
  split 'modules/acquia_cms_tour' drupal_acquia_cms_tour
  split 'modules/acquia_cms_video' drupal_acquia_cms_video
  split 'modules/sitestudio_config_management' sitestudio_config_management
}

if [[ ! -z ${MODULE} ]]; then
  FOUND=$([[ " ${acquia_cms_modules[*]} " =~ " ${MODULE} " ]] && echo "true" || echo "false")
  if [ "${FOUND}" = "false" ]; then
    echo -e "\n${RED_BG}ERROR: Invalid module name: \`${MODULE}\`.${NOCOLOR}"
    echo -e "\nModule name must be from one of the following:"
    for module in "${acquia_cms_modules[@]}"
    do
      echo -e " * ${GREEN}${module}${NOCOLOR}"
    done
    exit 1
  fi
fi

if [[ ! -z "$PUSH" && "$PUSH" = "drupal" ]]; then
  add_drupal_remote
else
  add_acquia_remote
fi
