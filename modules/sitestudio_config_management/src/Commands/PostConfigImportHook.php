<?php

namespace Drupal\sitestudio_config_management\Commands;

use Drupal\sitestudio_config_management\SiteStudioConfigManagement;
use Drupal\sitestudio_config_management\Traits\DrushCommandTrait;
use Drush\Commands\DrushCommands;

/**
 * Imports Site Studio packages, POST config:import command.
 */
class PostConfigImportHook extends DrushCommands {

  use DrushCommandTrait;

  /**
   * The site_studio.config_management service object.
   *
   * @var \Drupal\sitestudio_config_management\SiteStudioConfigManagement
   */
  protected $siteStudioConfigService;

  /**
   * Constructs the object.
   *
   * @param \Drupal\sitestudio_config_management\SiteStudioConfigManagement $site_studio_config_service
   *   The site_studio.config_management service object.
   */
  public function __construct(SiteStudioConfigManagement $site_studio_config_service) {
    $this->siteStudioConfigService = $site_studio_config_service;
    parent::__construct();
  }

  /**
   * Execute site studio package import on config:import.
   *
   * @hook post-command config-import
   */
  public function configImportPostCommand(): void {
    if (!$this->siteStudioConfigService->isSiteStudioConfigured()) {
      $this->logger->warning("Skipping Site studio package import as Site Studio is not configured.");
      return;
    }
    $this->addCommand("cohesion:import");
    $this->addCommand("sitestudio:package:import");
    $isSiteStudioUpgraded = $this->siteStudioConfigService->isSiteStudioUpgraded();
    if ($isSiteStudioUpgraded) {
      $this->addCommand("cohesion:rebuild");
    }
    if ($this->execute() && $isSiteStudioUpgraded) {
      $this->siteStudioConfigService->initialize();
    }
  }

}
