<?php

namespace Drush\Commands\acquia_global_commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;
use Drupal\acquia_config_management\Event\ConfigEvents;
use Drush\Commands\DrushCommands;

/**
 * Execute code on configuration export/import.
 */
class ConfigImportExportCommands extends DrushCommands implements SiteAliasManagerAwareInterface {

  use SiteAliasManagerAwareTrait;

  /**
   * Execute code on configuration export.
   *
   * @param \Consolidation\AnnotatedCommand\CommandResult|array|null $result
   *   The exit code from the main operation for config-export.
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The information about the current request.
   *
   * @hook post-command config-export
   */
  public function postConfigExportCommand($result, CommandData $commandData) {
    if (\Drupal::service('module_handler')->moduleExists("acquia_config_management")) {
      $event = new ConfigEvents($result, $commandData, $this);
      $event_dispatcher = \Drupal::service('event_dispatcher');
      $event_dispatcher->dispatch($event, ConfigEvents::POST_CONFIG_EXPORT);
    }
  }

  /**
   * Execute code on configuration import.
   *
   * @param \Consolidation\AnnotatedCommand\CommandResult|array|null $result
   *   The exit code from the main operation for config-export.
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The information about the current request.
   *
   * @hook post-command config-import
   */
  public function postConfigImportCommand($result, CommandData $commandData) {
    if (\Drupal::service('module_handler')->moduleExists("acquia_config_management")) {
      $status = \Drupal::service("acquia_config_management.site_install")->status();
      $event = new ConfigEvents($result, $commandData, $this);
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
   * Execute code after site installed with existing config.
   *
   * @param \Consolidation\AnnotatedCommand\CommandResult|array|null $result
   *   The exit code from the main operation for config-export.
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The information about the current request.
   *
   * @hook post-command site:install
   */
  public function siteInstallExistingConfigCommand($result, CommandData $commandData) {
    if (\Drupal::service('module_handler')->moduleExists("acquia_config_management")) {
      $options = $commandData->options();
      if (isset($options['existing-config']) && $options['existing-config']) {
        $event = new ConfigEvents($result, $commandData, $this);
        $event_dispatcher = \Drupal::service('event_dispatcher');
        $event_dispatcher->dispatch($event, ConfigEvents::POST_SITE_INSTALL_EXISTING_CONFIG);
      }
    }
  }

  /**
   * Run given drush command.
   *
   * @param string $command
   *   The command to execute.
   */
  public function runDrushCommand(string $command): void {
    $this->yell("Running $command command.");
    $selfAlias = $this->siteAliasManager()->getSelf();
    $process = $this->processManager()->drush($selfAlias, $command, [], []);
    $process->mustRun($process->showRealtime());
  }

}
