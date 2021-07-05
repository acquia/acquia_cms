<?php

namespace Drush\Commands;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector as Environment;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Core\Datetime\DrupalDateTime;

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
      $rebuild_start_time = new DrupalDateTime();
      $formatted = \Drupal::service('date.formatter')->format(
        $rebuild_start_time->getTimestamp(), 'custom', 'Y-m-d h:i:s'
      );
      \Drupal::state()->set('rebuild_start_time', $formatted);
      $this->say(dt('Rebuilding all entities.'));
      $result = \Drupal::service('acquia_cms_common.utility')->rebuildSiteStudio();
      $this->yell('Finished rebuilding.');
      $this->setFinishedTime();
      return is_array($result) && isset(array_shift($result)['error']) ? CommandResult::exitCode(self::EXIT_FAILURE) : CommandResult::exitCode(self::EXIT_SUCCESS);
    }
  }

  /**
   * Set site studio rebuild time and send data to telemetry.
   */
  public function setFinishedTime() {
    // Set rebuild time.
    $rebuild_end_time = new DrupalDateTime();
    $formatted = \Drupal::service('date.formatter')->format(
      $rebuild_end_time->getTimestamp(), 'custom', 'Y-m-d h:i:s'
    );
    \Drupal::state()->set('rebuild_end_time', $formatted);

    // Send data to telemetry based upon certain conditions.
    if (Drupal::service('module_handler')->moduleExists('acquia_telemetry') && Environment::isAhEnv()) {
      $rebuild_start_time = \Drupal::state()->get('rebuild_start_time');
      $rebuild_end_time = \Drupal::state()->get('rebuild_end_time');
      $rebuild_start_time = new DrupalDateTime($rebuild_start_time);
      $rebuild_end_time = new DrupalDateTime($rebuild_end_time);
      $rebuild_time_diff = $this->calculateTimeDiff($rebuild_start_time, $rebuild_end_time);
      $this->sendHeartbeatEvent($rebuild_time_diff);
    }
  }

  /**
   * Function to calculate the time difference.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_time
   *   Variable that stores the start time.
   * @param \Drupal\Core\Datetime\DrupalDateTime $end_time
   *   Variable that stores the end time.
   *
   * @return int
   *   Returns the time difference in seconds.
   */
  public function calculateTimeDiff(DrupalDateTime $start_time, DrupalDateTime $end_time) {
    // Perform the subtraction and return the time in seconds.
    $timeDiff = $end_time->getTimestamp() - $start_time->getTimestamp();
    // Return the difference.
    return $timeDiff;
  }

  /**
   * Function to send data to telemetry.
   *
   * @param int $time_diff
   *   Parameter contains the rebuild time difference.
   */
  public function sendHeartbeatEvent(int $time_diff) {
    Drupal::configFactory()
      ->getEditable('acquia_telemetry.settings')
      ->set('api_key', 'e896d8a97a24013cee91e37a35bf7b0b')
      ->save();
    \Drupal::service('acquia.telemetry')->sendTelemetry('acquia_cms_installed', [
      'Application UUID' => Environment::getAhApplicationUuid(),
      'Site Environment' => Environment::getAhEnv(),
      'Rebuild Time' => $time_diff,
    ]);
  }

}
