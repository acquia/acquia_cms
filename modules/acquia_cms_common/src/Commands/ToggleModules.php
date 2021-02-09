<?php

namespace Drupal\acquia_cms_common\Commands;

use Drupal\acquia_cms_common\ToggleModulesService;
use Drush\Commands\DrushCommands;

/**
 * Implements Drush command hooks.
 */
class ToggleModules extends DrushCommands {

  /**
   * Toggle module Service.
   *
   * @var Drupal\acquia_cms_common\ToggleModulesService
   */
  private $toggleModuleServices;

  /**
   * Constructs toggleModuleServices object.
   */
  public function __construct(ToggleModulesService $toggle_modules_services) {
    $this->toggleModuleServices = $toggle_modules_services;
  }

  /**
   * Drush command that toggles modules.
   *
   * @command acms:toggle:modules
   * @aliases atm
   */
  public function toggleModulesCommand() {
    $this->toggleModuleServices->toggleModules();
  }

}
