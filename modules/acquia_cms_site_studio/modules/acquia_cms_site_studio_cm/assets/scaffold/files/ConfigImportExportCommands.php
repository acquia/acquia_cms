<?php

namespace Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;

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
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('acquia_cms_site_studio') && $moduleHandler->moduleExists('acquia_cms_site_studio_cm')) {
      $configSyncDirectory = Settings::get('config_sync_directory');
      $cohesionSettingFile = $moduleHandler->getModule('acquia_cms_site_studio_cm')->getPath() . '/config/optional/cohesion.setting.yml';
      \Drupal::service('file_system')->copy($cohesionSettingFile, $configSyncDirectory, FileSystemInterface::EXISTS_REPLACE);
      $this->runDrushCommand('sitestudio:package:export');
    }
    return is_array($result) && isset(array_shift($result)['error']) ? CommandResult::exitCode(self::EXIT_FAILURE) : CommandResult::exitCode(self::EXIT_SUCCESS);
  }

  /**
   * Execute site studio package import on config import.
   *
   * @hook post-command config-import
   */
  public function configImportPostCommand($result, CommandData $commandData): CommandResult {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('acquia_cms_site_studio') && $moduleHandler->moduleExists('acquia_cms_site_studio_cm')) {
      // Get site studio credentials if its set.
      $siteStudioCredentials = _acquia_cms_site_studio_get_credentials();

      // Set credentials if module being installed independently.
      if ($siteStudioCredentials['status']) {
        _acquia_cms_site_studio_set_credentials($siteStudioCredentials['api_key'], $siteStudioCredentials['organization_key']);
        $this->runDrushCommand('coh:import');
        $this->runDrushCommand('sitestudio:package:import');
        _acquia_cms_site_studio_generate_image_assets();
        $this->runDrushCommand('cr');
      }
      else {
        $this->yell("Your Site Studio API KEY has not been set.");
      }
    }
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
      $this->yell("Running $command command.");
      $selfAlias = $this->siteAliasManager()->getSelf();
      $process = $this->processManager()->drush($selfAlias, $command, [], []);
      $process->mustRun($process->showRealtime());
    }
  }

}
