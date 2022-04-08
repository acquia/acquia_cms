<?php

namespace Drupal\acquia_cms\Commands;

use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 */
class DeprecateAcquiaCms extends DrushCommands {

  /**
   * Switch profile.
   *
   * @command acms:deprecate
   * @aliases acms-deprecate
   */
  public function switchAcmsToMinimal($profile = "minimal") {
    switch_profile_to_minimal($profile);
    $this->output()->writeln('Profile switched from ACMS to minimal successfully.');
  }

}
