<?php

namespace Drupal\acquia_cms_common\Services;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;

/**
 * Defines a ACMS telemetry service.
 */
final class AcmsTelemetryService {

  /**
   * The extension.list.module service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

  /**
   * The config factory.
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
   * Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The statePath service.
   *
   * @var string
   */
  protected $sitePath;

  /**
   * Drupal date time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new AcmsService object.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
   *   The extension.list.module service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger factory.
   * @param string $site_path
   *   Drupal Site Path.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    ModuleExtensionList $module_list,
    ConfigFactoryInterface $config_factory,
    StateInterface $state,
    LoggerChannelFactoryInterface $logger,
    string $site_path,
    TimeInterface $time) {
    $this->moduleList = $module_list;
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->logger = $logger;
    $this->sitePath = $site_path;
    $this->time = $time;
  }

  /**
   * Creates and log event to dblog/syslog.
   *
   * @param string $event_type
   *   The event type. This accepts any string that is not reserved.
   *   For reserve kerwords please visit official website.
   * @param array $event_properties
   *   (optional) Event properties.
   *
   * @throws \Exception|\GuzzleHttp\Exception\GuzzleException
   *   Thrown if state key acquia_telemetry.loud is TRUE and request fails.
   *
   * @see https://help.sumologic.com/docs/search/lookup-tables/create-lookup-table/#reserved-keywords
   */
  public function sendTelemetry(string $event_type, array $event_properties = []): void {
    $telemetryData = $this->createEvent($event_type, $event_properties ?: $this->getAcquiaCmsTelemetryData());
    if ($this->shouldSendTelemetryData($event_type, $telemetryData)) {
      // Failure to send Telemetry should never cause a user facing error or
      // interrupt a process. Telemetry failure should be graceful and quiet.
      try {
        // Logging the database and collecting data into Sumo Logic.
        $sumologicEventProperties = ['user_id' => $telemetryData['user_id']] + $telemetryData['event_properties'];
        $this->logger->get($event_type)->info('@message', [
          '@message' => json_encode($sumologicEventProperties, JSON_UNESCAPED_SLASHES),
        ]);
        $this->state->set('acquia_cms_common.telemetry.hash', $this->getHash($telemetryData));
        $this->state->set('acquia_cms_common.telemetry.timestamp', $this->time->getCurrentTime());

      }
      catch (\Exception $e) {
        if ($this->state->get('acquia_connector.telemetry.loud')) {
          throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

      }
    }

  }

  /**
   * Creates an telemetry event.
   *
   * @param string $type
   *   The event type.
   * @param array $properties
   *   The event properties.
   *
   * @return array
   *   An telemetry event with basic info already populated.
   */
  protected function createEvent(string $type, array $properties): array {
    $defaultProperties = [
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
      'event_properties' => NestedArray::mergeDeep($defaultProperties, $properties),
    ];
  }

  /**
   * Get Acquia CMS telemetry data.
   */
  protected function getAcquiaCmsTelemetryData(): array {
    $telemetryData = &drupal_static(__FUNCTION__, []);

    if (empty($telemetryData)) {
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
          'install_time' => $this->state->get('acquia_cms.telemetry.install_time', ''),
          'profile' => $this->configFactory->get('core.extension')->get('profile'),
        ],
        'extensions' => $this->getExtensionInfo(),
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

  /**
   * Check current environment.
   *
   * @return bool
   *   TRUE if Acquia production environment, otherwise FALSE.
   */
  protected function isAcquiaProdEnv(): bool {

    $ahEnv = getenv('AH_SITE_ENVIRONMENT');
    $ahEnv = preg_replace('/[^a-zA-Z0-9]+/', '', $ahEnv);

    // phpcs:disable
    // ACSF Sites should use the pre-configured env and db roles instead.
    if (isset($GLOBALS['gardens_site_settings'])) {
      $ahEnv = $GLOBALS['gardens_site_settings']['env'];
    }
    // phpcs:enable

    return ($ahEnv === 'prod' || preg_match('/^\d*live$/', $ahEnv));
  }

  /**
   * Decides if telemetry data should send or not.
   *
   * @param string $event_type
   *   The Event type name.
   * @param array $telemetry_data
   *   The array of telemetry data.
   *
   * @return bool
   *   TRUE if condition allow to send data, otherwise FALSE.
   */
  protected function shouldSendTelemetryData(string $event_type, array $telemetry_data): bool {
    $isCI = (bool) getenv("CI");

    if ($isCI || PHP_SAPI !== 'cli') {
      return FALSE;
    }

    // Only send telemetry data if we're in a production environment.
    if (!$this->isAcquiaProdEnv()) {
      return FALSE;
    }

    // Send telemetry data if there is change in current data to send
    // and previous sent telemetry data.
    if ($this->state->get("acquia_connector.telemetry.$event_type.hash") == $this->getHash($telemetry_data)) {
      return FALSE;
    }

    $sendTimestamp = $this->state->get('acquia_cms_common.telemetry.timestamp', 0);
    $isOpted = $this->state->get('acquia_connector.telemetry.opted', TRUE);

    // We send telemetry data if all below conditions are met:
    // If user has opted to send telemetry data.
    // If data is not sent from the last 24 hours.
    if ($isOpted && ($this->time->getCurrentTime() - $sendTimestamp) > 86400) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get an array of information about Acquia extensions.
   *
   * @return array
   *   An array of extension info keyed by the extensions machine name. E.g.,
   *   ['acquia_cms_common' => ['version' => '3.1.0', 'status' => 'enabled']].
   */
  public function getExtensionInfo(): array {
    $allModules = $this->moduleList->getAllAvailableInfo();
    $extensions = [];
    $installedModules = array_keys($this->moduleList->getAllInstalledInfo());

    foreach ($allModules as $name => $value) {
      // If (in_array())
      if (strpos($name, 'cohesion') !== FALSE || strpos($name, 'acquia') !== FALSE || strpos($name, 'sitestudio') !== FALSE) {
        $extensions[$name] = [
          // Version is unset for dev versions. In order to generate reports, we
          // need some value for version, even if it is just the major version.
          'version' => $value['version'] ?? 'dev',
          // Check if module is installed.
          'status' => in_array($name, $installedModules) ? "enabled" : "disabled",
        ];
      }
    }
    return $extensions;
  }

  /**
   * Gets a unique ID for this application. "User ID" to group all request.
   *
   * @return string
   *   Returns a hashed site uuid.
   */
  protected function getUserId(): string {
    return Crypt::hashBase64($this->configFactory->get('system.site')->get('uuid'));
  }

  /**
   * Gets a unique hash for telemetry data.
   *
   * @param array $telemetry_data
   *   The array of telemetry data.
   *
   * @return string
   *   Returns a hash of telemetry data.
   */
  private function getHash(array $telemetry_data): string {
    return Crypt::hashBase64(serialize($telemetry_data));
  }

}
