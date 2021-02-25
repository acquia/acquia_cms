<?php

namespace Drupal\acquia_cms_common\Services;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Defines a service for ACMS.
 */
class AcmsUtilityService {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new AcmsService object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The ModuleHandlerInterface.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Fetch acquia cms profile with list of enabled modules of ACMS.
   */
  public function getAcquiaCmsProfileModuleList(): array {
    $profile_modules = $this->moduleHandler->getModuleList();
    return array_filter($profile_modules, function ($key) {
      return str_starts_with($key, 'acquia_cms');
    }, ARRAY_FILTER_USE_KEY);
  }

}
