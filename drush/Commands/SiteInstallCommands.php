<?php

namespace Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\cohesion\Drush\DX8CommandHelpers;

/**
 * Rebuild site studio when site is installed via drush.
 */
class SiteInstallCommands extends DrushCommands {

  /**
   * Execute site studio rebuild after Acquia CMS site install.
   *
   * @hook post-command site-install
   */
  public function postCommand($result, CommandData $commandData) {
    $arguments = $commandData->arguments();
    $moduleHandler = \Drupal::service('module_handler');
    if (isset($arguments['profile'][0]) && $arguments['profile'][0] == 'acquia_cms' && $moduleHandler->moduleExists('cohesion')) {
      // Forcefully clear the cache after site is installed otherwise site
      // studio fails to rebuild.
      drupal_flush_all_caches();
      // Below code ensures that drush batch process doesn't hang. Unset all the
      // earlier created batches so that drush_backend_batch_process() can run
      // without being stuck.
      // @see https://github.com/drush-ops/drush/issues/3773 for the issue.
      $batch = &batch_get();
      $batch = NULL;
      unset($batch);
      $this->say(dt('Rebuilding all entities.'));
      $result = DX8CommandHelpers::rebuild([]);
      // Output results.
      $this->yell('Finished rebuilding.');
      // Status code.
      return is_array($result) && isset(array_shift($result)['error']) ? CommandResult::exitCode(self::EXIT_FAILURE) : CommandResult::exitCode(self::EXIT_SUCCESS);
    }
  }

}
