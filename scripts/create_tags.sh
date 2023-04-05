#!/bin/bash

cd "$(dirname "$0")"
# Color codes definition.
GREEN="\033[0;32m"
YELLOW="\033[0;33m"
NOCOLOR="\033[0m"
RED="\033[0;31m"
RED_BG="\033[41m"
WHITE="\033[1;37m"

# Displays the command help text.
display_help() {
    echo -e "Usage: $0 --{option}={value}"; echo>&2
    echo -e "${YELLOW}Examples:${NOCOLOR}"
    echo -e "  $0 --module=acquia_cms_common \t  Create tag for the \`acquia_cms_common\` module."
    echo -e "  $0 --module=all \t  Create tag for all \`acquia_cms\` modules."
    echo -e "  $0 --tag=1.0.0-beta1 \t  Create tag 1.0.0-beta1 for given \`acquia_cms\` module."
    echo -e "  $0 --type=minor \t  Create minor release for given \`acquia_cms\` module."
    echo
    echo -e "${YELLOW}Options:${NOCOLOR}"
    echo -e "  --module[=MODULE] \t\t Create tag for given acquia_cms module."
    echo -e "  --type[=TAG] \t\t Create given tag for given acquia_cms module."
    echo -e "  --type[=TYPE] \t\t Create given release type for given acquia_cms module."
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
        --module=) # Handle the case of an empty
           display_value_error $1
            ;;
        --module=?*)
            MODULE=${1#*=} # Delete everything up to "=" and assign the remainder.
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
        --tag=) # Handle the case of an empty
           display_value_error $1
            ;;
        --tag=?*)
            TAG=${1#*=} # Delete everything up to "=" and assign the remainder.
            ;;
        --type)
          if [[ $2 == -* ]]; then
            display_value_error $1
          fi
          if [ $2 ]; then
                TYPE=$2
                shift
            else
              display_value_error $1
            fi
            ;;
        --type=) # Handle the case of an empty
           display_value_error $1
            ;;
        --type=?*)
            TYPE=${1#*=} # Delete everything up to "=" and assign the remainder.
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

if [ -z "${MODULE}" -o -z "${TYPE}" ]; then
  echo -e "${RED_BG}${WHITE}ERROR: Pass all required option i.e --module and --type.${NOCOLOR}\n"
  display_help
fi


if [ "${MODULE}" != "all" ]; then
  modules=$(php ./parse_release.php --module=${MODULE})
else
  modules=$(php ./parse_release.php -a)
fi

mkdir -p /tmp/modules && cd /tmp/modules
echo "${modules}" | while read -r module_line;
do
  module_name=$(echo "${module_line}" | sed 's/module: \(.*\) tag.*/\1/')
  tag=$(echo "${module_line}" | sed 's/.* tag: \(.*\) dev.*/\1/')
  dev=$(echo "${module_line}" | sed 's/.*dev: \(.*\)/\1/')
  major=$(echo "${tag}" | cut -d'.' -f1)
  minor=$(echo "${tag}" | cut -d'.' -f2)
  patch=$(echo "${tag}" | cut -d'.' -f3)
  if [ -z "${TAG}" ]; then
    if [ "${TYPE}" = "major" ]; then
      ((major++))
      minor=0
      patch=0
    elif [ "${TYPE}" = "minor" ]; then
      ((minor++))
      patch=0
    elif [ "${TYPE}" = "patch" ]; then
      ((patch++))
    else
      echo -e "${RED_BG}${WHITE}ERROR: Option --type with value ${TYPE} is invalid.${NOCOLOR}\n"
    fi
    nextTag=${major}.${minor}.${patch}
  else
    nextTag=${TAG}
  fi

  git clone --single-branch -b ${dev} git@git.drupal.org:project/${module_name}.git &> log.txt

  # If git clone was un-successful, display error and exit with status code 1.
  if [ $? -ne 0 ]; then
    cat log.txt
    exit 1
  fi

  cd ${module_name}
  git fetch origin --tags &> log.txt

  # If git fetch was un-successful, display error and exit with status code 1.
  if [ $? -ne 0 ]; then
    cat log.txt
    exit 1
  fi

  if [ "${MODULE}" != "acquia_cms_common" ]; then
    git merge ${tag} --no-edit &> log.txt

    # If git merge was un-successful, display error and exit with status code 1.
    if [ $? -ne 0 ]; then
        cat log.txt
        exit 1
    fi

  fi

  git tag ${nextTag} &> log.txt

  # If git tag was un-successful, display error and exit with status code 1.
  if [ $? -ne 0 ]; then
      cat log.txt
      exit 1
  fi

  git push origin ${nextTag} -f &> log.txt
  if [ $? -ne 0 ]; then
    echo -e "${RED_BG}${WHITE}ERROR: The code push for the module: '${module_name}' failed.${NOCOLOR}\n"
    cat log.txt
    exit 1
  else
    CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
    echo -e "${GREEN}Tag pushed successful for the module: '${module_name}.${NOCOLOR}"
    echo -e " - Dev Branch: ${GREEN}${CURRENT_BRANCH}${NOCOLOR}, Previous Tag: ${GREEN}${tag}${NOCOLOR}, New Tag: ${GREEN}${nextTag}${NOCOLOR}.\n"
  fi
  cd - &> log.txt
done
