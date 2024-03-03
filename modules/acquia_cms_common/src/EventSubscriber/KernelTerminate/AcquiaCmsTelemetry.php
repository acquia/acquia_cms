<?php

namespace Drupal\acquia_cms_common\EventSubscriber\KernelTerminate;

use Drupal\acquia_cms_common\Services\AcmsTelemetryService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Acquia CMS Telemetry Event Subscriber.
 *
 * This event sends anonymized data to Acquia to help track Acquia CMS usage
 * and versions Acquia sites use to ensure Acquia CMS module updates don't break
 * customer sites.
 *
 * @package Drupal\acquia_cms_common\EventSubscriber
 */
class AcquiaCmsTelemetry implements EventSubscriberInterface {

  /**
   * Acquia Telemetry Service.
   *
   * @var \Drupal\acquia_cms_common\Services\AcmsTelemetryService
   */
  private AcmsTelemetryService $acmsTelemetryService;

  /**
   * Constructs a telemetry object.
   *
   * @param \Drupal\acquia_cms_common\Services\AcmsTelemetryService $acms_telemetry
   *   The Acquia CMS Telemetry Service.
   */
  public function __construct(AcmsTelemetryService $acms_telemetry) {
    $this->acmsTelemetryService = $acms_telemetry;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::TERMINATE][] = ['onTerminateResponse'];
    return $events;
  }

  /**
   * Sends Telemetry once. This occurs after the response is sent.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event.
   *
   * @throws \Exception
   */
  public function onTerminateResponse(KernelEvent $event): void {
    $this->acmsTelemetryService->sendTelemetry("ACMS Telemetry data");
  }

}
