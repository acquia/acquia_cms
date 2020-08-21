#!/bin/sh

cp misc/acms.settings.php docroot/sites/acms.settings.php
cat >> docroot/sites/default/settings.php << EOL
### ACMS SETTINGS ###
require DRUPAL_ROOT . '/sites/acms.settings.php';
### END ACMS SETTINGS ###
EOL
