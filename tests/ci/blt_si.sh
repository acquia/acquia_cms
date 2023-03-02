#!/usr/bin/env bash

./vendor/bin/drush config:set system.site uuid ${UUID}
./vendor/bin/drush cex --yes
./vendor/bin/drush site:install minimal --yes --uri=http://127.0.0.1:8080
./vendor/bin/drush config:set system.site uuid ${UUID} --yes
./vendor/bin/drush cim --yes
./vendor/bin/drush config:status
