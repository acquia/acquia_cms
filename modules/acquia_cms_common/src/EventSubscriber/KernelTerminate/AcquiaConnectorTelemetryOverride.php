<?php

namespace Drupal\acquia_cms_common\EventSubscriber\KernelTerminate;

use Drupal\acquia_connector\EventSubscriber\KernelTerminate\AcquiaTelemetry;
use Drupal\acquia_connector\Services\AcquiaTelemetryService;
use Drupal\Core\State\StateInterface;
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
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs an Acquia CMS telemetry object.
   *
   * @param \Drupal\acquia_connector\Services\AcquiaTelemetryService $acquia_telemetry_service
   *   The Acquia Telemetry Service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(AcquiaTelemetryService $acquia_telemetry_service, StateInterface $state) {
    parent::__construct($acquia_telemetry_service);
    $this->state = $state;
  }

  /**
   * Sends Telemetry on a daily basis. This occurs after the response is sent.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event.
   */
  public function onTerminateResponse(KernelEvent $event) {
    // Check if telemetry opted,
    // then only trigger AcquiaTelemetry::onTerminateResponse($event).
    if (PHP_SAPI !== 'cli' && $this->state->get('acquia_connector.telemetry.opted')) {
      parent::onTerminateResponse($event);
    }
  }

}
