<?php

namespace Drupal\acquia_cms_common;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector as Environment;

/**
 * Defines a service provider for module toggle.
 */
class ToggleModulesService {

  /**
   * {@inheritdoc}
   */
  public function toggleModules() {
    $is_dev = Environment::isAhIdeEnv() || Environment::isLocalEnv();
    $is_prod = Environment::isAhProdEnv();
    $module_installer = \Drupal::service('module_installer');
    $to_install = [];
    $to_uninstall = [];
    if ($is_dev) {
      array_push($to_install, 'dblog', 'jsonapi_extras');
      array_push($to_uninstall, 'syslog', 'autologout');
    }
    else {
      array_push($to_install, 'syslog', 'autologout');
    }
    if (!$is_prod) {
      array_push($to_install, 'reroute_email');
    }
    // @todo once PF-3025 has been resolved, update this to work on IDEs too.
    if (Environment::isAhEnv() && !Environment::isAhIdeEnv()) {
      array_push($to_install, 'imagemagick');
      \Drupal::configFactory()
        ->getEditable('imagemagick.settings')
        ->set('path_to_binaries', '/usr/bin/')
        ->save();
      \Drupal::configFactory()
        ->getEditable('system.image')
        ->set('toolkit', 'imagemagick')
        ->save();
    }
    $module_installer->install($to_install);
    $module_installer->uninstall($to_uninstall);
  }

}
