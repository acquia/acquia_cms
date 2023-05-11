<?php

namespace Drupal\acquia_cms_image\Config;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to configuration events for the Default Content module.
 */
class AcquiaCmsImageConfigSubscriber implements EventSubscriberInterface {

  /**
   * Creates default content after config synchronization.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The config importer event.
   */
  public function onConfigImport(ConfigImporterEvent $event) {
    _acquia_cms_image_import_logo();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [ConfigEvents::IMPORT => 'onConfigImport'];
  }

}
