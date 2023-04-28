<?php

namespace Drupal\acquia_cms_site_studio_cm\EventSubscriber;

use Drupal\acquia_config_management\Commands\DrushCommand;
use Drupal\acquia_config_management\Event\PostConfigEvent;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
   * The drush command object.
   *
   * @var \Drupal\ACMS_1730_namespace\Commands\DrushCommand
   */
  protected $drushCommand;

  /**
   * Constructs a new ConfigEventsSubscriber object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The ModuleHandlerInterface.
   * @param \Drupal\acquia_config_management\Commands\DrushCommand $drush_command
   *   The Drush command object.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    DrushCommand $drush_command) {
    $this->moduleHandler = $module_handler;
    $this->drushCommand = $drush_command;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      PostConfigEvent::POST_CONFIG_IMPORT => 'onPostConfigImport',
      PostConfigEvent::POST_CONFIG_EXPORT => 'onPostConfigExport',
    ];
  }

  /**
   * Post config import manipulation.
   */
  public function onPostConfigImport($event) {
    if ($this->moduleHandler->moduleExists('acquia_cms_site_studio') && $this->moduleHandler->moduleExists('acquia_cms_site_studio_cm')) {
      // Get site studio credentials if its set.
      $siteStudioCredentials = _acquia_cms_site_studio_get_credentials();

      // Set credentials if module being installed independently.
      if ($siteStudioCredentials['status']) {
        _acquia_cms_site_studio_set_credentials($siteStudioCredentials['api_key'], $siteStudioCredentials['organization_key']);
        $event->getDrushCommand()->execute('coh:import');
        $this->drushCommand->execute('coh:import');
        $this->drushCommand->execute('sitestudio:package:import');
        $this->drushCommand->execute('cr');
      }
    }
  }

  /**
   * Post config export manipulation.
   */
  public function onPostConfigExport() {
    if ($this->moduleHandler->moduleExists('acquia_cms_site_studio') && $this->moduleHandler->moduleExists('acquia_cms_site_studio_cm')) {
      $configSyncDirectory = Settings::get('config_sync_directory');
      $cohesionSettingFile = $this->moduleHandler->getModule('acquia_cms_site_studio_cm')->getPath() . '/config/optional/cohesion.settings.yml';
      \Drupal::service('file_system')->copy($cohesionSettingFile, $configSyncDirectory, FileSystemInterface::EXISTS_REPLACE);
      $this->drushCommand->execute('sitestudio:package:export');
    }
  }

}
