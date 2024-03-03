<?php

namespace Drupal\acquia_cms_common\EventSubscriber\KernelTerminate;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\acquia_connector\Services\AcquiaTelemetryService;
use Drupal\Core\Config\ConfigFactoryInterface;
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
   * Acquia Telemetry Service.
   *
   * @var \Drupal\acquia_connector\Services\AcquiaTelemetryService
   */
  protected AcquiaTelemetryService $telemetryService;

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
   * The statePath service.
   *
   * @var string
   */
  protected $sitePath;

  /**
   * Constructs a new AcmsService object.
   *
   * @param \Drupal\acquia_connector\Services\AcquiaTelemetryService $acquia_telemetry
   *   The Acquia Telemetry Service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param string $site_path
   *   Drupal Site Path.
   */
  public function __construct(
    AcquiaTelemetryService $acquia_telemetry,
    ConfigFactoryInterface $config_factory,
    StateInterface $state,
    string $site_path) {
    $this->telemetryService = $acquia_telemetry;
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->sitePath = $site_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
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
    if ($this->state->get('acquia_connector.telemetry.opted', TRUE) && PHP_SAPI !== 'cli') {
      $this->telemetryService->sendTelemetry("ACMS Telemetry data", $this->getAcquiaCmsTelemetryData());
    }
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
