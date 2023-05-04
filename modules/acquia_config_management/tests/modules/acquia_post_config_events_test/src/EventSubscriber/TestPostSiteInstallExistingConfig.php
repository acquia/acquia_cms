<?php

namespace Drupal\acquia_post_config_events_test\EventSubscriber;

use Drupal\acquia_config_management\Event\ConfigEvents;
use Drupal\acquia_post_config_events_test\Traits\ConfigEventLogTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EntityTypeSubscriber.
 *
 * @package Drupal\acquia_config_management\EventSubscriber
 */
class TestPostSiteInstallExistingConfig implements EventSubscriberInterface {

  use ConfigEventLogTrait;

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::POST_SITE_INSTALL_EXISTING_CONFIG => 'postSiteInstallExistingConfig',
    ];
  }

  /**
   * React to a config object being saved.
   *
   * @param \Drupal\acquia_config_management\Event\ConfigEvents $event
   *   Config crud event.
   */
  public function postSiteInstallExistingConfig(ConfigEvents $event) {
    $this->log('existing_site_install', __METHOD__);
  }

}
