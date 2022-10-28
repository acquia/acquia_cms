<?php

namespace Drupal\acquia_cms\Facade;

use Drupal\acquia_connector\EventSubscriber\KernelTerminate\AcquiaConnectorTelemetryOverride;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a facade for acquia connector Telemetry Event.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class TelemetryFacade implements ContainerInjectionInterface {

  /**
   * The telemetry service.
   *
   * @var \Drupal\acquia_common\EventSubscriber\KernelTerminate\AcquiaConnectorTelemetryOverride
   */
  private $telemetry;

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  private $moduleList;

  /**
   * TelemetryFacade constructor.
   *
   * @param \Drupal\acquia_common\EventSubscriber\KernelTerminate\AcquiaConnectorTelemetryOverride $telemetry_service
   *   The telemetry service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
   *   The module extension list service.
   */
  public function __construct(AcquiaConnectorTelemetryOverride $telemetry_service, ModuleExtensionList $module_list) {
    $this->telemetry = $telemetry_service;
    $this->moduleList = $module_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('acquia_connector.telemetry'),
      $container->get('extension.list.module')
    );
  }

  /**
   * Send an event to Amplitude with installed extension meta data.
   *
   * @param string[] $modules
   *   The modules which have been installed.
   */
  public function modulesInstalled(array $modules) : void {
    $event_properties = [
      'installed_extensions' => array_values($modules),
      'previously_installed_extensions' => array_values(array_diff(array_keys($this->moduleList->getAllInstalledInfo()), $modules)),
    ];
    $this->telemetry->sendTelemetry('Extensions installed', $event_properties);
  }

  /**
   * Send an event to Amplitude with uninstalled extension meta data.
   *
   * @param string[] $modules
   *   The modules which have been uninstalled.
   */
  public function modulesUninstalled(array $modules) : void {
    $event_properties = [
      'uninstalled_extensions' => array_values($modules),
      'previously_installed_extensions' => array_values(array_diff(array_keys($this->moduleList->getAllInstalledInfo()), $modules)),
    ];
    $this->telemetry->sendTelemetry('Extensions uninstalled', $event_properties);
  }

}
