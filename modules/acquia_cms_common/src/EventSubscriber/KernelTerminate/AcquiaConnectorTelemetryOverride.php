<?php

namespace Drupal\acquia_cms_common\EventSubscriber\KernelTerminate;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
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
    if (\Drupal::state()->get('acquia_connector.telemetry.opted', TRUE) && PHP_SAPI !== 'cli') {
      \Drupal::service('acquia_connector.telemetry_service')->sendTelemetry("ACMS Telemetry data", $this->getAcquiaCmsTelemetryData());
      parent::onTerminateResponse($event);
    }
  }

  /**
   * Get Acquia CMS telemetry data.
   */
  protected function getAcquiaCmsTelemetryData(): array {
    $state = \Drupal::state();
    $config = \Drupal::configFactory();
    $telemetryData = &drupal_static(__FUNCTION__, []);
    $sitePath = \Drupal::getContainer()->getParameter('site.path');
    if (empty($telemetryData)) {
      $siteUri = explode('/', $sitePath);
      // Telemetry Event Properties.
      $telemetryData = [
        'acquia_cms' => [
          'application_uuid' => AcquiaDrupalEnvironmentDetector::getAhApplicationUuid(),
          'application_name' => AcquiaDrupalEnvironmentDetector::getAhGroup(),
          'environment_name' => AcquiaDrupalEnvironmentDetector::getAhEnv(),
          'acsf_status' => AcquiaDrupalEnvironmentDetector::isAcsfEnv(),
          'site_uri' => end($siteUri),
          'site_name' => $config->get('system.site')->get('name'),
          'starter_kit_name' => $config->get('acquia_cms_common.settings')->get('starter_kit_name'),
          'starter_kit_ui' => $state->get('starter_kit_wizard_completed', FALSE),
          'site_studio_status' => $this->siteStudioStatus(),
          'install_time' => $state->get('acquia_cms.telemetry.install_time', ''),
          'profile' => $config->get('core.extension')->get('profile'),
        ],
      ];
    }

    return $telemetryData;
  }

  /**
   * Get Site studio status.
   */
  protected function siteStudioStatus(): bool {
    if (\Drupal::getContainer()->has('cohesion.utils') &&
      \Drupal::service('cohesion.utils')->usedx8Status()) {
      return TRUE;
    }

    return FALSE;
  }

}
