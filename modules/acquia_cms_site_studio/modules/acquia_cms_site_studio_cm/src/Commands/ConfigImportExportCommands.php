<?php

namespace Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;

/**
 * Execute site studio package export/import on config export/import.
 */
class ConfigImportExportCommands extends DrushCommands implements SiteAliasManagerAwareInterface {

  use SiteAliasManagerAwareTrait;

  /**
   * Execute site studio package export on config export.
   *
   * @hook post-command config-export
   */
  public function configExportPostCommand($result, CommandData $commandData): CommandResult {

    $this->runDrushCommand('sitestudio:package:export');
    return is_array($result) && isset(array_shift($result)['error']) ? CommandResult::exitCode(self::EXIT_FAILURE) : CommandResult::exitCode(self::EXIT_SUCCESS);
  }

  /**
   * Execute site studio package import on config import.
   *
   * @hook post-command config-import
   */
  public function configImportPostCommand($result, CommandData $commandData): CommandResult {

    $this->runDrushCommand('sitestudio:package:import');
    return is_array($result) && isset(array_shift($result)['error']) ? CommandResult::exitCode(self::EXIT_FAILURE) : CommandResult::exitCode(self::EXIT_SUCCESS);
  }

  /**
   * Run given drush command.
   *
   * @param string $command
   *   The command to execute.
   */
  private function runDrushCommand(string $command): void {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('acquia_cms_site_studio') && $moduleHandler->moduleExists('acquia_cms_site_studio_cm')) {
      $this->yell("Running site $command command.");
      $selfAlias = $this->siteAliasManager()->getSelf();
      $process = $this->processManager()->drush($selfAlias, $command, [], []);
      $process->mustRun($process->showRealtime());
    }
  }

}
