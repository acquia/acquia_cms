<?php

namespace Drupal\acquia_cms_support\Controller;

use Drupal\acquia_cms_support\Service\AcquiaCmsConfigSyncService;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Return page with list of overridden configuration related to acquia cms.
 */
class AcquiaCmsConfigSyncOverridden extends ControllerBase implements ContainerInjectionInterface {

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
      $this->t('Default parity'),
      $this->t('Operations'),
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
        $links = $this->getViewDifference($key, $value->getType(), $storage, $config_file);

        if ($delta <= 30) {
          $class_name = 'color-parity-30';
        }
        elseif ($delta > 30 && $delta <= 75) {
          $class_name = 'color-parity-75';
        }
        else {
          $class_name = 'color-parity-above-75';
        }
        if ($delta != '100') {
          $rows[] = [
            'name' => $config_file,
            'last_modified' => '12/01/2020',
            'config' => [
              'class' => $class_name,
              'data' => ['#markup' => "<span>$delta  %</span>"],
            ],
            'operations' => [
              'data' => [
                '#type' => 'operations',
                '#links' => $links,
              ],
            ],
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

  /**
   * Get the view difference link for each configuration.
   *
   * @param string $name
   *   The configuration name.
   * @param string $type
   *   The entity type.
   * @param string $storage
   *   The storage.
   * @param string $config_file
   *   The configuration file.
   *
   * @return array
   *   The diff link in modal.
   */
  public function getViewDifference($name, $type, $storage, $config_file) {
    $links = [];
    $route_name = 'acquia_cms_support.config_diff';
    $route_options = [
      'name' => $name,
      'type' => $type,
      'storage' => $storage,
      'source_name' => $config_file,
    ];
    $links['view_diff'] = [
      'title' => $this->t('View differences'),
      'url' => Url::fromRoute($route_name, $route_options),
      'attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => json_encode([
          'width' => 700,
          'buttons' => [
            [
              'text' => 'OK',
              'class' => 'button button--primary',
              'click' => "function() { $(this).dialog('close'); }",
            ],
          ],
        ]),
      ],
    ];
    return $links;
  }

}
