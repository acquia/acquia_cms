<?php

namespace Drush\Commands;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector as Environment;
use Acquia\Utility\AcquiaTelemetry;
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
  public function siteInstallPostCommand($result, CommandData $commandData): CommandResult {
    $arguments = $commandData->arguments();
    $moduleHandler = \Drupal::service('module_handler');
    $config = \Drupal::config('cohesion.settings');
    $cohesion_configured = $config->get('api_key') && $config->get('organization_key');
    if ($moduleHandler->moduleExists('acquia_cms_site_studio') && $cohesion_configured) {
      if (isset($arguments['profile'][0]) && $arguments['profile'][0] == 'acquia_cms') {
        $telemetry = \Drupal::classResolver(AcquiaTelemetry::class);
        $telemetry->setTime('rebuild_start_time');
      }
      $this->say(dt('Rebuilding all entities.'));
      $result = \Drupal::service('acquia_cms_common.utility')->rebuildSiteStudio();
      $this->yell('Finished rebuilding.');
      if (isset($arguments['profile'][0]) && $arguments['profile'][0] == 'acquia_cms') {
        $telemetry->setTime('rebuild_end_time');
      }
    }
    // Send data to telemetry based upon certain conditions.
    if ($moduleHandler->moduleExists('acquia_telemetry') && Environment::isAhEnv()) {
      if (function_exists('acquia_cms_send_heartbeat_event')) {
        acquia_cms_send_heartbeat_event();
      }
    }
    return is_array($result) && isset(array_shift($result)['error']) ? CommandResult::exitCode(self::EXIT_FAILURE) : CommandResult::exitCode(self::EXIT_SUCCESS);
  }

}
