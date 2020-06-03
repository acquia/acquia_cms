<?php

namespace Drupal\acquia_cms_common\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\DrushCommands;

/**
 * Implements Drush command hooks.
 */
final class Hooks extends DrushCommands {

  /**
   * Alters the result of the config:get command.
   *
   * @hook alter config:get
   * @option $generic Automatically strip the UUID and site hash.
   * @usage config:get --generic system.site
   */
  public function processConfig($result, CommandData $command_data) {
    if ($command_data->input()->getOption('generic')) {
      unset($result['uuid'], $result['_core']);
    }
    return $result;
  }

}
