<?php

namespace Drupal\sitestudio_config_management\Config;

use Drupal\cohesion_sync\Config\CohesionStorageComparer;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\sitestudio_config_management\SiteStudioConfigManagement;
use Drupal\sitestudio_config_management\Traits\DrushCommandTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to configuration events for the drush config import command.
 */
class ConfigImportEventSubscriber implements EventSubscriberInterface {

  use DrushCommandTrait;

  /**
   * The site_studio.config_management service object.
   *
   * @var \Drupal\sitestudio_config_management\SiteStudioConfigManagement
   */
  protected $siteStudioConfigService;

  /**
   * The logger service object.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs config_import event object.
   *
   * @param \Drupal\sitestudio_config_management\SiteStudioConfigManagement $site_studio_config_service
   *   The site_studio.config_management service object.
   * @param \Psr\Log\LoggerInterface $logger
   *   Holds logger interface object.
   */
  public function __construct(SiteStudioConfigManagement $site_studio_config_service, LoggerInterface $logger) {
    $this->logger = $logger;
    $this->siteStudioConfigService = $site_studio_config_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [ConfigEvents::IMPORT => 'onConfigImport'];
  }

  /**
   * Imports Site Studio configurations during site:install.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The config importer event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drush\Exceptions\UserAbortException
   */
  public function onConfigImport(ConfigImporterEvent $event) {
    if ($event->getConfigImporter()->getStorageComparer() instanceof CohesionStorageComparer) {
      return;
    }
    if (!$this->siteStudioConfigService->isSiteStudioConfigured()) {
      $this->logger->warning("Skipping Site Studio package import as Site Studio is not configured.");
      return;
    }
    $enabled_extensions = $event->getConfigImporter()->getExtensionChangelist('module', 'install');

    // Here we import Site Studio configurations, only during site installation
    // from existing configurations. After each config:import command, we
    // handle the import of Site Studio configurations using the drush
    // post-command config-import hook.
    // @see \Drupal\sitestudio_config_management\Commands\PostConfigImportHook
    // Because in scenarios, where there are NO Drupal Configuration changes,
    // the event ConfigEvents::IMPORT is not triggered.
    if (in_array('sitestudio_config_management', $enabled_extensions)) {
      $this->addCommand("sitestudio:package:import");
      $this->addCommand("cohesion:import");
      $this->execute();
    }
  }

  /**
   * Returns the logger service object.
   */
  protected function logger(): LoggerInterface {
    return $this->logger;
  }

}
