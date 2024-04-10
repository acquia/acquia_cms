<?php

namespace Drupal\acquia_cms_common\EventSubscriber\KernelTerminate;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
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
   * The extension.list.module service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

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
   * Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs an Acquia CMS telemetry object.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
   *   The extension.list.module service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param string $site_path
   *   Drupal Site Path.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger factory.
   */
  public function __construct(
    ModuleExtensionList $module_list,
    ConfigFactoryInterface $config_factory,
    StateInterface $state,
    string $site_path,
    TimeInterface $time,
    LoggerChannelFactoryInterface $logger) {
    $this->moduleList = $module_list;
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->sitePath = $site_path;
    $this->time = $time;
    $this->logger = $logger;
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
    if ($this->shouldSendTelemetryData() && PHP_SAPI !== 'cli') {
      $this->sendTelemetry("ACMS Telemetry data", $this->getAcquiaCmsTelemetryData());
    }
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
   * @return bool
   *   TRUE if event was successfully sent, otherwise FALSE.
   *
   * @throws \Exception|\GuzzleHttp\Exception\GuzzleException
   *   Thrown if state key acquia_telemetry.loud is TRUE and request fails.
   *
   * @see https://help.sumologic.com/docs/search/lookup-tables/create-lookup-table/#reserved-keywords
   */
  public function sendTelemetry(string $event_type, array $event_properties = []): bool {
    $event = $this->createEvent($event_type, $event_properties);

    // Failure to send Telemetry should never cause a user facing error or
    // interrupt a process. Telemetry failure should be graceful and quiet.
    try {
      // Logging the database and collecting data into Sumo Logic.
      $sumologicEventProperties = ['user_id' => $event['user_id']] + $event['event_properties'];
      $this->logger->get($event_type)->info('@message', [
        '@message' => json_encode($sumologicEventProperties, JSON_UNESCAPED_SLASHES),
      ]);
      $this->state->set('acquia_cms_common.telemetry.hash', $this->getHash());
      $this->state->set('acquia_cms_common.telemetry.timestamp', $this->time->getCurrentTime());

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
  private function createEvent(string $type, array $properties): array {
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
  private function getAcquiaCmsTelemetryData(): array {
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

    $sendTimestamp = $this->state->get('acquia_cms_common.telemetry.timestamp', 0);

    // We send telemetry data if all below conditions are met:
    // If current environment is Acquia environment.
    // If data is not sent from the last 24 hours.
    // If there is change in telemetry data to send & previous telemetry data.
    if (AcquiaDrupalEnvironmentDetector::isAhEnv() &&
    ($this->time->getCurrentTime() - $sendTimestamp) > 86400 &&
    !($this->state->get('acquia_cms_common.telemetry.hash') == $this->getHash())) {
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
  public function getExtensionInfo() {
    $allModules = $this->moduleList->getAllAvailableInfo();
    $extensions = [];
    $installedModules = array_keys($this->moduleList->getAllInstalledInfo());
    foreach ($allModules as $name => $value) {
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
  private function getUserId(): string {
    return Crypt::hashBase64($this->configFactory->get('system.site')->get('uuid'));
  }

  /**
   * Gets a unique hash for telemetry data.
   *
   * @return string
   *   Returns a hashed telemetry data.
   */
  private function getHash(): string {
    return Crypt::hashBase64(serialize($this->getAcquiaCmsTelemetryData()));
  }

}
