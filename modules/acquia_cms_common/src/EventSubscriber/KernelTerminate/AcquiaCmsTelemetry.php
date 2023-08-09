<?php

namespace Drupal\acquia_cms_common\EventSubscriber\KernelTerminate;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\Component\Datetime\TimeInterface;
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
   * The statePath service.
   *
   * @var string
   */
  protected $sitePath;

  /**
   * Drupal Time Service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

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
   * @param string $site_path
   *   Drupal Site Path.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    ModuleExtensionList $module_list,
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    StateInterface $state,
    string $site_path,
    TimeInterface $time) {
    $this->moduleList = $module_list;
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->sitePath = $site_path;
    $this->time = $time;
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
    $send_timestamp = $this->state->get('acquia_connector.telemetry.timestamp');
    if ($this->shouldSendTelemetryData() &&
    $this->time->getCurrentTime() - $send_timestamp > 86400) {
      $event_properties = $this->getAcquiaCmsTelemetryData();
      $this->sendTelemetry("ACMS Telemetry data", $event_properties);
      $this->state->set('acquia_connector.telemetry.timestamp', $this->time->getCurrentTime());
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
      $this->state->set('acquia_cms_common.telemetry_data', json_encode($event_properties));
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
      'php' => [
        'version' => phpversion(),
      ],
      'drupal' => [
        'version' => \Drupal::VERSION,
      ],
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
    $siteUri = explode('/', $this->sitePath);

    // Telemetry Event Properties.
    $telemetryData = [
      'acquia_cms' => [
        'application_uuid' => AcquiaDrupalEnvironmentDetector::getAhApplicationUuid(),
        'application_name' => AcquiaDrupalEnvironmentDetector::getAhGroup(),
        'environment_name' => AcquiaDrupalEnvironmentDetector::getAhEnv(),
        'acsf_status' => AcquiaDrupalEnvironmentDetector::isAcsfEnv(),
        'site_uri' => end($siteUri),
        'site_name' => $this->configFactory->get('system.site')->get('name'),
        'starter_kit_name' => $this->configFactory->get('acquia_cms_common.settings')->get('starter_kit_name'),
        'starter_kit_ui' => $this->state->get('starter_kit_wizard_completed', FALSE),
        'site_studio_status' => $this->siteStudioStatus(),
        'profile' => $this->configFactory->get('core.extension')->get('profile'),
      ],
      'extensions' => $this->getExtensionInfo(),
    ];
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
      if ($telemetryOpted && !$isAcquiaTelemetryDataSent && $this->shouldResendData()) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Decides if telemetry data should resend or not.
   */
  private function shouldResendData(): bool {
    $telemetryDataOld = $this->state->get('acquia_cms_common.telemetry_data', []);
    if (!empty($telemetryDataOld)) {
      $telemetryDataOld = json_decode($telemetryDataOld, TRUE);
    }
    $telemetryDataCurrent = $this->getAcquiaCmsTelemetryData();
    if (empty($telemetryDataOld) || (is_array($telemetryDataOld) &&
    array_key_exists('acquia_cms', $telemetryDataOld) &&
    array_diff_assoc($telemetryDataOld['acquia_cms'], $telemetryDataCurrent['acquia_cms']))) {
      $this->state->set('acquia_cms_telemetry.status', FALSE);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get an array of information about Lightning extensions.
   *
   * @return array
   *   An array of extension info keyed by the extensions machine name. E.g.,
   *   ['lightning_layout' => ['version' => '8.2.0', 'status' => 'enabled']].
   */
  private function getExtensionInfo() {
    $all_modules = $this->moduleList->getAllAvailableInfo();
    $installed_modules = $this->moduleList->getAllInstalledInfo();
    $extension_info = [];
    $acms_modules = [
      'acquia_cms_article',
      'acquia_cms_audio',
      'acquia_cms_common',
      'acquia_cms_component',
      'acquia_cms_dam',
      'acquia_cms_document',
      'acquia_cms_event',
      'acquia_cms_headless',
      'acquia_cms_image',
      'acquia_cms_page',
      'acquia_cms_person',
      'acquia_cms_place',
      'acquia_cms_search',
      'acquia_cms_site_studio',
      'acquia_cms_starter',
      'acquia_cms_tour',
      'acquia_cms_toolbar',
      'acquia_cms_video',
      'sitestudio_config_management',
    ];

    foreach ($all_modules as $name => $extension) {
      // Remove all custom modules from reporting.
      if (strpos($this->moduleList->getPath($name), '/custom/') !== FALSE) {
        continue;
      }
      if (in_array($name, $acms_modules)) {
        // Version is unset for dev versions. In order to generate reports, we
        // need some value for version, even if it is just the major version.
        $extension_info[$name]['version'] = $extension['version'] ?? 'dev';

        // Check if module is installed.
        $extension_info[$name]['status'] = array_key_exists($name, $installed_modules) ? 'enabled' : 'disabled';
      }
    }

    return $extension_info;
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
