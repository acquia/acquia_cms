<?php

namespace Drupal\sitestudio_config_management\Drush\Commands;

use Drupal\sitestudio_config_management\SiteStudioConfigManagement;
use Drupal\sitestudio_config_management\Traits\DrushCommandTrait;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exports Site Studio packages, POST config:export command.
 */
class PostConfigExportHook extends DrushCommands {

  use DrushCommandTrait;

  /**
   * The site_studio.config_management service object.
   */
  protected SiteStudioConfigManagement $siteStudioConfigService;

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('site_studio.config_management')
    );
  }

  /**
   * Execute site studio package export on config:export.
   *
   * @hook post-command config:export
   */
  public function configExportPostCommand(): void {
    if (!$this->siteStudioConfigService->isSiteStudioConfigured()) {
      $this->logger->warning("Skipping Site studio package export as Site Studio is not configured.");
      return;
    }
    $this->addCommand("sitestudio:package:export");
    $this->execute();
  }

}
