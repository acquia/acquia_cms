<?php

namespace Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\acquia_cms_config_management\Event\ConfigEvents;

/**
 * Execute code on configuration export/import.
 */
class ConfigImportExportCommands extends DrushCommands {

  /**
   * Execute code on configuration export.
   *
   * @param array|null $result
   *   The exit code from the main operation for config-export.
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The information about the current request.
   *
   * @hook post-command config-export
   */
  public function postConfigExportCommand($result, CommandData $commandData) {
    if (\Drupal::service('module_handler')->moduleExists("acquia_cms_config_management")) {
      $event = new ConfigEvents($result, $commandData);
      $event_dispatcher = \Drupal::service('event_dispatcher');
      $event_dispatcher->dispatch($event, ConfigEvents::POST_CONFIG_EXPORT);
    }
  }

  /**
   * Execute code on configuration import.
   *
   * @param array|null $result
   *   The exit code from the main operation for config-export.
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The information about the current request.
   *
   * @hook post-command config-import
   */
  public function postConfigImportCommand($result, CommandData $commandData) {
    if (\Drupal::service('module_handler')->moduleExists("acquia_cms_config_management")) {
      $status = \Drupal::service("acquia_cms_config_management.site_install")->status();
      $event = new ConfigEvents($result, $commandData);
      $event_dispatcher = \Drupal::service('event_dispatcher');
      if ($status) {
        $event_dispatcher->dispatch($event, ConfigEvents::POST_SITE_INSTALL_EXISTING_CONFIG);
      }
      else {
        $event_dispatcher->dispatch($event, ConfigEvents::POST_CONFIG_IMPORT);
      }
    }
  }

  /**
   * Execute code on configuration import.
   *
   * @param array|null $result
   *   The exit code from the main operation for config-export.
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The information about the current request.
   *
   * @hook post-command site:install
   */
  public function siteInstallExistingConfigCommand($result, CommandData $commandData) {
    if (\Drupal::service('module_handler')->moduleExists("acquia_cms_config_management")) {
      $options = $commandData->options();
      if (isset($options['existing-config']) && $options['existing-config']) {
        $event = new ConfigEvents($result, $commandData);
        $event_dispatcher = \Drupal::service('event_dispatcher');
        $event_dispatcher->dispatch($event, ConfigEvents::POST_SITE_INSTALL_EXISTING_CONFIG);
      }
    }
  }

}
