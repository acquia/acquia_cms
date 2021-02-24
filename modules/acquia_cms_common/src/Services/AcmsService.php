<?php

namespace Drupal\acquia_cms_common\Services;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Defines a service for ACMS.
 */
class AcmsService {

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
   * Fetch the list of enabled modules of ACMS.
   */
  public function getModuleList(): array {
    $modules = $this->moduleHandler->getModuleList();
    $acms_modules = [];
    foreach ($modules as $module => $module_obj) {
      if ($module_obj->getType() === 'module' && str_starts_with($module_obj->getName(), 'acquia_cms')) {
        $acms_modules[] = $module;
      }
    }
    return $acms_modules;
  }

}
