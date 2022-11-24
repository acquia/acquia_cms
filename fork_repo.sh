declare -a MODULES
while read -r module_name; read -r type; read -r url; read -r canonical
do
  module_name=$(echo "${module_name}" | sed 's/\://')
  type=$(echo "${type}" | sed 's/.*\"\(.*\)\"/\1/')
  url=$(echo "${url}" | sed 's/.*\"\(.*\)\"/\1/')
  canonical=$(echo "${canonical}" | sed 's/.*\: //')
  json='{ "type": '"\"${type}"\"', "url": '"\"${url}\""', "canonical": '"${canonical}"' }'
  composer config repositories.${module_name} "${json}"
  MODULES+=("${module_name}")
done < ./tests/repositories_alter.yml
EXCLUDE=$(echo "\"${MODULES[@]}\"" | sed 's/ /", "/g')
drupal_json='{ "type": "composer", "url": "https://packages.drupal.org/8" , "exclude": ['${EXCLUDE}'] }'
composer config repositories.drupal "${drupal_json}"

CURRENT_DIR=$(pwd)
find modules -type d -maxdepth 1 | sed '1d' | while read -r sub_module_name;
do
  SUBMODULE_DIR=${CURRENT_DIR}/${sub_module_name}
  composer config repositories.drupal "${drupal_json}" -d ${SUBMODULE_DIR}
done
