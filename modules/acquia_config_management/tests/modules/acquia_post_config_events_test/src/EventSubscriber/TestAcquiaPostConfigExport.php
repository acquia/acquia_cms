<?php

namespace Drupal\acquia_post_config_events_test\EventSubscriber;

use Drupal\acquia_config_management\Event\ConfigEvents;
use Drupal\acquia_config_management\EventSubscriber\AcquiaPostConfigExport;
use Drupal\acquia_post_config_events_test\Traits\ConfigEventLogTrait;

/**
 * Class EntityTypeSubscriber.
 *
 * @package Drupal\acquia_config_management\EventSubscriber
 */
class TestAcquiaPostConfigExport extends AcquiaPostConfigExport {

  use ConfigEventLogTrait;

  /**
   * React to a config object being saved.
   *
   * @param \Drupal\acquia_config_management\Event\ConfigEvents $event
   *   Config crud event.
   */
  public function postConfigExport(ConfigEvents $event) {
    parent::postConfigExport($event);
    $this->log('export', __METHOD__);
  }

}
