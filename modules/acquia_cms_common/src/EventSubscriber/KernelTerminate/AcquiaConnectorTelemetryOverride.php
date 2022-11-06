<?php

namespace Drupal\acquia_cms_common\EventSubscriber\KernelTerminate;

use Drupal\acquia_connector\EventSubscriber\KernelTerminate\AcquiaTelemetry;
use Symfony\Component\HttpKernel\Event\KernelEvent;

/**
 * Acquia Telemetry Event Subscriber for Acquia CMS.
 *
 * This event sends anonymized data to Acquia to help track modules and versions
 * Acquia sites use to ensure module updates don't break customer sites.
 * Also Acquia CMS provides opt-in functionality.
 *
 * @package Drupal\acquia_cms_common\EventSubscriber
 */
class AcquiaConnectorTelemetryOverride extends AcquiaTelemetry {

  /**
   * Sends Telemetry on a daily basis. This occurs after the response is sent.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event.
   */
  public function onTerminateResponse(KernelEvent $event) {
    // Check if telemetry opted,
    // then only trigger AcquiaTelemetry::onTerminateResponse($event).
    if ($this->state->get('acquia_connector.telemetry.opted', TRUE)) {
      parent::onTerminateResponse($event);
    }
  }

}
