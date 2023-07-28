<?php

namespace Drupal\acquia_cms_common\EventSubscriber\KernelTerminate;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
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
   * Amplitude API URL.
   *
   * @var string
   * @see https://developers.amplitude.com/#http-api
   */
  protected $apiUrl = 'https://api.amplitude.com/httpapi';

  /**
   * The extension.list.module service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs an Acquia CMS telemetry object.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
   *   The extension.list.module service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(
    ModuleExtensionList $module_list,
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    StateInterface $state) {
    $this->moduleList = $module_list;
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->state = $state;
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
    if ($this->shouldSendTelemetryData()) {
      $event_properties = $this->getAcquiaCmsTelemetryData();
      $this->sendTelemetry("ACMS Telemetry data", $event_properties);
      $this->state->set('acquia_cms_telemetry.status', TRUE);
    }
  }

  /**
   * Returns the Amplitude API key.
   *
   * This is not intended to be private. It is typically included in client
   * side code. Fetching data requires an additional API secret.
   *
   * @see https://developers.amplitude.com/#http-api
   *
   * @return string
   *   The Amplitude API key.
   */
  private function getApiKey(): string {
    return Settings::get('acquia_connector.telemetry.key', 'e896d8a97a24013cee91e37a35bf7b0b');
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
  private function sendEvent(array $event): void {
    $this->httpClient->request('POST', $this->apiUrl, [
      'form_params' => [
        'api_key' => $this->getApiKey(),
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
   * @param array $event_properties
   *   (optional) Event properties.
   *
   * @return bool
   *   TRUE if event was successfully sent, otherwise FALSE.
   *
   * @throws \Exception
   *   Thrown if state key acquia_telemetry.loud is TRUE and request fails.
   *
   * @see https://amplitude.zendesk.com/hc/en-us/articles/204771828#keys-for-the-event-argument
   */
  public function sendTelemetry(string $event_type, array $event_properties = []): bool {
    $event = $this->createEvent($event_type, $event_properties);

    // Failure to send Telemetry should never cause a user facing error or
    // interrupt a process. Telemetry failure should be graceful and quiet.
    try {
      $this->sendEvent($event);
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
   * Creates an Amplitude event.
   *
   * @param string $type
   *   The event type.
   * @param array $properties
   *   The event properties.
   *
   * @return array
   *   An Amplitude event with basic info already populated.
   */
  private function createEvent(string $type, array $properties): array {
    $default_properties = [
      'php' => phpversion(),
      'drupal' => \Drupal::VERSION,
    ];

    return [
      'event_type' => $type,
      'user_id' => $this->getUserId(),
      'event_properties' => NestedArray::mergeDeep($default_properties, $properties),
    ];
  }

  /**
   * Get Acquia CMS telemetry data.
   */
  private function getAcquiaCmsTelemetryData(): array {
    $appUuid = AcquiaDrupalEnvironmentDetector::getAhApplicationUuid();
    $siteGroup = AcquiaDrupalEnvironmentDetector::getAhGroup();
    $env = AcquiaDrupalEnvironmentDetector::getAhEnv();
    $starterKitName = $this->configFactory->get('acquia_cms_common.settings')->get('starter_kit_name') ?? $this->state->get('acquia_cms.starter_kit', "acquia_cms_existing_site");
    $starterKitUi = $this->state->get('starter_kit_wizard_completed', FALSE);
    $installed_modules = $this->moduleList->getAllInstalledInfo();
    $profile = $this->configFactory->get('core.extension')->get('profile');

    $telemetryData = [
      'acquia_cms' => [
        'application_uuid' => $appUuid,
        'application_name' => $siteGroup,
        'environment_name' => $env,
        'starter_kit_name' => $starterKitName,
        'starter_kit_ui' => $starterKitUi,
        'site_studio_status' => $this->siteStudioStatus(),
        'profile' => $profile,
      ],
    ];
    if (isset($installed_modules['acquia_cms'])) {
      $telemetryData['acquia_cms']['version'] = $installed_modules['acquia_cms']['version'];
    }
    return $telemetryData;
  }

  /**
   * Get Site studio status.
   */
  private function siteStudioStatus(): bool {
    if (\Drupal::getContainer()->has('cohesion.utils') &&
      \Drupal::service('cohesion.utils')->usedx8Status()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Decides if telemetry data should send or not.
   */
  private function shouldSendTelemetryData(): bool {
    $isCI = (bool) getenv("CI");
    if ($isCI) {
      return FALSE;
    }
    if (AcquiaDrupalEnvironmentDetector::isAhEnv()) {
      $telemetryOpted = $this->state->get('acquia_connector.telemetry.opted', TRUE);
      $isAcquiaTelemetryDataSent = $this->state->get('acquia_cms_telemetry.status', FALSE);
      if ($telemetryOpted && !$isAcquiaTelemetryDataSent) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Gets a unique ID for this application. "User ID" is an Amplitude term.
   *
   * @return string
   *   Returns a hashed site uuid.
   */
  private function getUserId(): string {
    return Crypt::hashBase64($this->configFactory->get('system.site')->get('uuid'));
  }

}
