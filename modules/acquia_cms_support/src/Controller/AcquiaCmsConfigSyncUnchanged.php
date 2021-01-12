<?php

namespace Drupal\acquia_cms_support\Controller;

use Drupal\acquia_cms_support\Service\AcquiaCmsConfigSyncService;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a route controller providing a Config sync for acquia cms.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
class AcquiaCmsConfigSyncUnchanged extends ControllerBase implements ContainerInjectionInterface {

  /**
   * AcquiaCmsConfigSyncService.
   *
   * @var \Drupal\acquia_cms_support\Service\AcquiaCmsConfigSyncService
   */
  protected $acmsConfigSync;

  /**
   * AcquiaCmsConfigSyncOverridden constructor.
   *
   * @param \Drupal\acquia_cms_support\Service\AcquiaCmsConfigSyncService $acms_config_sync
   *   The acquia cms config sync.
   */
  public function __construct(AcquiaCmsConfigSyncService $acms_config_sync) {
    $this->acmsConfigSync = $acms_config_sync;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('acquia_cms_support.config_service')
    );
  }

  /**
   * Returns a renderable array for a configuration page.
   */
  public function build() {
    $header = [
      $this->t('Name'),
      $this->t('Last modified'),
    ];
    $rows = [];
    $acquia_cms_profile_modules = $this->acmsConfigSync->getAcquiaCmsProfileModuleList();

    foreach ($acquia_cms_profile_modules as $key => $value) {
      $type = $value->getType();
      $config_path = ($type === 'profile') ? '../' : '../modules/' . $key;

      if (!is_dir($config_path . '/config')) {
        // No config directory move to next module.
        continue;
      }
      $config_install_dir = $config_path . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY;
      $config_optional_dir = $config_path . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY;

      if (!$config_install_dir && !$config_optional_dir) {
        // No install or optional directory, move to next module.
        continue;
      }

      $installed_list = $this->acmsConfigSync->getConfigList($config_install_dir, 'install');
      $optional_list = $this->acmsConfigSync->getConfigList($config_optional_dir, 'optional');
      $config_files_list = array_merge($installed_list, $optional_list);
      foreach ($config_files_list as $config_file => $storage) {
        $storage_config_path = ($type === 'profile') ? '../config/' . $storage : '../modules/' . $key . '/config/' . $storage;
        $sync_storage = $this->acmsConfigSync->getFileStorage($storage_config_path);
        $delta = $this->acmsConfigSync->getDelta($config_file, $sync_storage);

        if ($delta == '100') {
          $rows[] = [
            'name' => $config_file,
            'last_modified' => '12/01/2020',
          ];
        }
      }
    }
    asort($rows);

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

}
