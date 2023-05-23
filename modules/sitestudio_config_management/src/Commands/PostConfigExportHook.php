<?php

namespace Drupal\sitestudio_config_management\Commands;

use Drupal\cohesion_sync\Commands\CohesionSyncExportCommand;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\sitestudio_config_management\SiteStudioConfigManagement;
use Drush\Commands\DrushCommands;

/**
 * Exports Site Studio packages, POST config:export command.
 */
class PostConfigExportHook extends DrushCommands {

  /**
   * The cohesion_sync.commands.export command service object.
   *
   * @var \Drupal\cohesion_sync\Commands\CohesionSyncExportCommand
   */
  protected $cohesionSyncExportCommand;

  /**
   * The site_studio.config_management service object.
   *
   * @var \Drupal\sitestudio_config_management\SiteStudioConfigManagement
   */
  protected $siteStudioConfigService;

  /**
   * CohesionSyncCommands constructor.
   *
   * @param \Drupal\cohesion_sync\Commands\CohesionSyncExportCommand $cohesion_sync_export_command
   *   The cohesion_sync.commands.export service object.
   * @param \Drupal\sitestudio_config_management\SiteStudioConfigManagement $site_studio_config_service
   *   The site_studio.config_management service object.
   */
  public function __construct(CohesionSyncExportCommand $cohesion_sync_export_command, SiteStudioConfigManagement $site_studio_config_service) {
    $this->cohesionSyncExportCommand = $cohesion_sync_export_command;
    $this->siteStudioConfigService = $site_studio_config_service;
    parent::__construct();
  }

  /**
   * Execute site studio package export on config:export.
   *
   * @hook post-command config-export
   */
  public function configExportPostCommand(): void {
    if (!$this->siteStudioConfigService->isSiteStudioConfigured()) {
      $this->logger->warning("Skipping Site studio package export as Site Studio is not configured.");
      return;
    }
    try {
      $this->cohesionSyncExportCommand->siteStudioExport();
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $this->logger()->error($e->getMessage());
    }
  }

}
