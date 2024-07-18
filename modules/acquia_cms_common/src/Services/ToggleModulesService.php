<?php

namespace Drupal\acquia_cms_common\Services;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector as Environment;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleInstallerInterface;

/**
 * Defines a service that toggle modules based on environment.
 */
class ToggleModulesService {

  /**
   * The module extention list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extentionList;

  /**
   * The Module Installer Interface.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * Constructs a new ACMS toggle module object.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $extention_list
   *   The extention list.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The ModuleHandlerInterface.
   */
  public function __construct(
    ModuleExtensionList $extention_list,
    ModuleInstallerInterface $module_installer,
  ) {
    $this->extentionList = $extention_list;
    $this->moduleInstaller = $module_installer;
  }

  /**
   * Toggle module based on environment.
   */
  public function toggleModules() {
    $is_dev = Environment::isAhIdeEnv() || Environment::isLocalEnv();
    $is_prod = Environment::isAhProdEnv();
    $to_uninstall = [];
    $to_install = [];
    if ($is_dev) {
      array_push($to_install, 'dblog', 'field_ui', 'views_ui');
      if ($this->validateModuleExist('jsonapi_extras')) {
        array_push($to_install, 'jsonapi_extras');
      }
      array_push($to_uninstall, 'autologout');
    }
    else {
      array_push($to_install, 'autologout');
    }
    if (!$is_prod) {
      if ($this->validateModuleExist('reroute_email')) {
        array_push($to_install, 'reroute_email');
      }
    }
    // @todo once PF-3025 has been resolved, update this to work on IDEs too.
    if (Environment::isAhEnv() && !Environment::isAhIdeEnv()) {
      if ($this->validateModuleExist('imagemagick')) {
        array_push($to_install, 'imagemagick');
      }
    }
    $this->moduleInstaller->install($to_install);
    $this->moduleInstaller->uninstall($to_uninstall);
  }

  /**
   * Checks if given module exist.
   *
   * @param string $module
   *   Given module machine_name.
   *
   * @return bool
   *   Returns true|false based on module exist and on successful installation.
   *
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException
   * @throws \Drupal\Core\Extension\MissingDependencyException
   */
  protected function validateModuleExist(string $module): bool {
    try {
      if ($this->extentionList instanceof ModuleExtensionList) {
        $this->extentionList->get($module);
      }
    }
    catch (UnknownExtensionException $e) {
      return FALSE;
    }
    return TRUE;
  }

}
