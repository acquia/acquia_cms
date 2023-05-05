<?php

namespace Drupal\acquia_cms_site_studio\EventSubscriber;

use Drupal\acquia_config_management\Event\ConfigEvents;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Process site studio related manipulation on post configuration import/export.
 */
class PostConfigEventsSubscriber implements EventSubscriberInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The file system interface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new ConfigEventsSubscriber object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The ModuleHandlerInterface.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   Lets us create a directory.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    FileSystemInterface $file_system) {
    $this->moduleHandler = $module_handler;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    $events = [];
    if (class_exists(ConfigEvents::class)) {
      $events[ConfigEvents::POST_CONFIG_IMPORT] = 'onPostConfigImport';
      $events[ConfigEvents::POST_CONFIG_EXPORT] = 'onPostConfigExport';
    }

    return $events;
  }

  /**
   * Post config import manipulation.
   */
  public function onPostConfigImport($event) {
    // Get site studio credentials if its set.
    $siteStudioCredentials = _acquia_cms_site_studio_get_credentials();

    // Set credentials if module being installed independently.
    if ($siteStudioCredentials['status']) {
      _acquia_cms_site_studio_set_credentials($siteStudioCredentials['api_key'], $siteStudioCredentials['organization_key']);
      // $event->getDrushCommand()->execute('coh:import');
      // $event->getDrushCommand()->execute('coh:import');
      // $event->getDrushCommand()->execute('sitestudio:package:import');
      // $event->getDrushCommand()->execute('cr');
    }
  }

  /**
   * Post config export manipulation.
   */
  public function onPostConfigExport() {
    $configSyncDirectory = Settings::get('config_sync_directory');
    $cohesionSettingFile = $this->moduleHandler->getModule('acquia_cms_site_studio')->getPath() . '/config/optional/cohesion.settings.yml';
    $this->fileSystem->copy($cohesionSettingFile, $configSyncDirectory, FileSystemInterface::EXISTS_REPLACE);
    // $event->getDrushCommand()->execute('sitestudio:package:export');
  }

}
