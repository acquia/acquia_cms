<?php

namespace Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;

/**
 * Rebuild site studio when site is installed via drush.
 */
class SiteInstallCommands extends DrushCommands {

  /**
   * Execute site studio rebuild after Acquia CMS site install.
   *
   * @hook post-command site-install
   */
  public function siteInstallPostCommand($result, CommandData $commandData) {
    $arguments = $commandData->arguments();
    $moduleHandler = \Drupal::service('module_handler');
    if (isset($arguments['profile'][0]) && $arguments['profile'][0] == 'acquia_cms' && $moduleHandler->moduleExists('cohesion')) {
      $this->say(dt('Rebuilding all entities.'));
      $result = \Drupal::service('acquia_cms_common.utility')->rebuildSiteStudio();
      $this->yell('Finished rebuilding.');
      return is_array($result) && isset(array_shift($result)['error']) ? CommandResult::exitCode(self::EXIT_FAILURE) : CommandResult::exitCode(self::EXIT_SUCCESS);
    }
  }

}
