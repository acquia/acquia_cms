<?php

namespace Drupal\sitestudio_config_management;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Defines a service for Site Studio config management.
 */
class SiteStudioConfigManagement {

  /**
   * Stores the Site Studio current version.
   *
   * @var string|null
   */
  protected $currentVersion;

  /**
   * Stores the Site Studio previous version.
   *
   * @var string|null
   */
  protected $previousVersion;

  /**
   * Determines if Site Studio is configured or not.
   *
   * @var bool
   */
  protected $isConfigured;

  /**
   * The extension.list.module service object.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The state service object.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * The config.factory service object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger interface object.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs the service.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The extension.list.module service object.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service object.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger interface object.
   */
  public function __construct(ModuleExtensionList $module_extension_list, StateInterface $state, ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    $this->moduleExtensionList = $module_extension_list;
    $this->state = $state;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
  }

  /**
   * Sets some default values.
   */
  public function initialize(): void {
    $this->setVersionInState();
  }

  /**
   * Returns the Current Site Studio Version.
   */
  public function getCurrentVersion(): ?string {
    return $this->currentVersion ?? $this->getVersion();
  }

  /**
   * Returns the Previous Site Studio Version.
   */
  public function getPreviousVersion(): ?string {
    return $this->previousVersion ?? $this->getVersionFromState();
  }

  /**
   * Clear the Previous Version from Drupal State.
   */
  public function clear(): void {
    // Logic to delete the SiteStudio version info from state.
    $this->state->delete("sitestudio_config_management.site_studio_version");
    $this->previousVersion = NULL;
  }

  /**
   * Checks if Site Studio module is upgraded.
   */
  public function isSiteStudioUpgraded(): bool {
    $previousVersion = $this->getPreviousVersion();
    $currentVersion = $this->getCurrentVersion();
    if ($currentVersion && $previousVersion) {
      if (version_compare($currentVersion, $previousVersion, '>')) {
        return TRUE;
      }
    }
    else {
      $this->logger->warning("Can not determine, if Site Studio is upgraded.");
    }
    return FALSE;
  }

  /**
   * Determines, if Site Studio is configured.
   */
  public function isSiteStudioConfigured(): bool {
    $siteStudioConfig = $this->configFactory->get("cohesion.settings");
    return $siteStudioConfig->get("api_key") && $siteStudioConfig->get("organization_key");
  }

  /**
   * Returns Site Studio version from Drupal state.
   */
  private function getVersionFromState(): ?string {
    return $this->state->get("sitestudio_config_management.site_studio_version", $this->getCurrentVersion());
  }

  /**
   * Stores the Site Studio version in Drupal State.
   */
  private function setVersionInState(): void {
    $this->state->set("sitestudio_config_management.site_studio_version", $this->getCurrentVersion());
  }

  /**
   * Returns Site Studio version number from cohesion module's info.yml file.
   */
  private function getVersion(): ?string {
    if ($this->currentVersion) {
      return $this->currentVersion;
    }
    try {
      $extensionInfo = $this->moduleExtensionList->get("cohesion")->info;
      return $extensionInfo['version'];
    }
    catch (UnknownExtensionException $exception) {
      $this->logger->error($exception->getMessage());
    }
    return NULL;
  }

}
