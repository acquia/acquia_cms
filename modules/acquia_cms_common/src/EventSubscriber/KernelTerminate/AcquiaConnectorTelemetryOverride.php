<?php

namespace Drupal\acquia_cms_common\EventSubscriber\KernelTerminate;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\acquia_connector\EventSubscriber\KernelTerminate\AcquiaTelemetry;
use Drupal\Component\Serialization\Json;
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
   * Sends Telemetry once on ACMS installation.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event.
   */
  public function onTerminateResponse(KernelEvent $event) {
    // Check if telemetry opted,
    // then only trigger AcquiaTelemetry::onTerminateResponse($event).
    if ($this->state->get('acquia_connector.telemetry.opted', TRUE)) {
      if ($this->state->get('acquia_cms_telemetry.telemetry') === NULL) {
        $eventType = 'ACMS Telemetry data';
        $this->sendAcmsTelemetry($eventType);
      }
      parent::onTerminateResponse($event);
    }
  }

  /**
   * Sends an event to Amplitude.
   *
   * @param array $event
   *   The Amplitude event.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *
   * @see https://developers.amplitude.com/#http-api
   */
  private function sendAcmsEvent(array $event) {
    $this->httpClient->request('POST', $this->apiUrl, [
      'form_params' => [
        'api_key' => 'e896d8a97a24013cee91e37a35bf7b0b',
        'event' => Json::encode($event),
      ],
    ]);
  }

  /**
   * Creates and sends an event to Amplitude.
   *
   * @param string $event_type
   *   The event type. This accepts any string that is not reserved. Reserved
   *   event types include: "[Amplitude] Start Session", "[Amplitude] End
   *   Session", "[Amplitude] Revenue", "[Amplitude] Revenue (Verified)",
   *   "[Amplitude] Revenue (Unverified)", and "[Amplitude] Merged User".
   *
   * @return bool
   *   TRUE if event was successfully sent, otherwise FALSE.
   *
   * @throws \Exception
   *   Thrown if state key acquia_telemetry.loud is TRUE and request fails.
   *
   * @see https://amplitude.zendesk.com/hc/en-us/articles/204771828#keys-for-the-event-argument
   */
  public function sendAcmsTelemetry($event_type): bool {
    $event = $this->sendTelemetryDataToAmplitude($event_type);

    // Failure to send Telemetry should never cause a user facing error or
    // interrupt a process. Telemetry failure should be graceful and quiet.
    try {
      $this->sendAcmsEvent($event);
      return TRUE;
    }
    catch (\Exception $e) {
      if ($this->state->get('acquia_connector.telemetry.loud')) {
        throw new \Exception($e->getMessage(), $e->getCode(), $e);
      }
      return FALSE;
    }
  }

  /**
   * Send telemetry data to amplitude.
   *
   * @param string $type
   *   The event type.
   */
  public function sendTelemetryDataToAmplitude(string $type): array {
    if (AcquiaDrupalEnvironmentDetector::isAhEnv()) {
      $appUuid = AcquiaDrupalEnvironmentDetector::getAhApplicationUuid();
      $siteGroup = AcquiaDrupalEnvironmentDetector::getAhGroup();
      $env = AcquiaDrupalEnvironmentDetector::getAhEnv();
      $starterKitName = $this->state->get('acquia_cms.starter_kit');
      $starterKitUi = $this->state->get('starter_kit_wizard_completed', FALSE);
      $installed_modules = $this->moduleList->getAllInstalledInfo();
      $acmsVersion = '';
      if ($installed_modules['acquia_cms']) {
        $acmsVersion = $installed_modules['acquia_cms']['version'];
      }

      // Set Telemetry to true.
      $this->state->set('acquia_cms_telemetry.telemetry', TRUE);
      return [
        'event_type' => $type,
        'user_id' => $appUuid,
        'event_properties' => [
          'acquia_cms' => [
            'application_uuid' => $appUuid,
            'application_name' => $siteGroup,
            'environment_name' => $env,
            'starter_kit_name' => $starterKitName,
            'starter_kit_ui' => $starterKitUi,
            'site_studio_status' => $this->siteStudioStatus(),
            'acms_version' => $acmsVersion,
          ],
        ],
      ];
    }
    return [];
  }

  /**
   * Get Site studio status.
   */
  public function siteStudioStatus(): bool {
    if (\Drupal::getContainer()->has('cohesion.utils') &&
      \Drupal::service('cohesion.utils')->usedx8Status()) {
      return TRUE;
    }
    return FALSE;
  }

}
