<?php

namespace Drupal\acquia_cms_config_management\EventSubscriber;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\acquia_cms_config_management\Event\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The post configuration export event subscriber.
 *
 * @package Drupal\acquia_cms_config_management\EventSubscriber
 */
class AcquiaPostConfigExport implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::POST_CONFIG_EXPORT => ['postConfigExport', 100],
    ];
  }

  /**
   * React to after configurations are exported.
   *
   * @param \Drupal\acquia_cms_config_management\Event\ConfigEvents $event
   *   Config crud event.
   */
  public function postConfigExport(ConfigEvents $event,) {
    if (!AcquiaDrupalEnvironmentDetector::isAhIdeEnv() && AcquiaDrupalEnvironmentDetector::isAhEnv()) {
      $event->stopPropagation();
    }
  }

}
