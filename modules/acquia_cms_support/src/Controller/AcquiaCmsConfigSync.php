<?php

namespace Drupal\acquia_cms_support\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\ImportStorageTransformer;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a route controller providing a Config sync for acquia cms.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
class AcquiaCmsConfigSync extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The target storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $targetStorage;

  /**
   * The import transformer service.
   *
   * @var \Drupal\Core\Config\ImportStorageTransformer
   */
  protected $importTransformer;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * AcquiaCmsConfigSync constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   *   The target storage.
   * @param \Drupal\Core\Config\ImportStorageTransformer $import_transformer
   *   The import transformer service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StorageInterface $target_storage, ImportStorageTransformer $import_transformer, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->targetStorage = $target_storage;
    $this->importTransformer = $import_transformer;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.storage'),
      $container->get('config.import_transformer'),
      $container->get('module_handler')
    );
  }

  /**
   * Returns a renderable array for a configuration page.
   */
  public function build() {
    $header = [
      $this->t('Name'),
      $this->t('Default parity'),
      $this->t('Operations'),
    ];
    $rows = [];
    $acquia_cms_profile_modules = $this->getAcquiaCmsProfileModuleList();

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

      $installed_list = $this->getConfigList($config_install_dir, 'install');
      $optional_list = $this->getConfigList($config_optional_dir, 'optional');
      $config_files_list = array_merge($installed_list, $optional_list);

      foreach ($config_files_list as $config_file => $storage) {
        $storage_config_path = ($type === 'profile') ? '../config/' . $storage : '../modules/' . $key . '/config/' . $storage;
        $sync_storage = $this->getFileStorage($storage_config_path);
        $delta = $this->getDelta($config_file, $sync_storage);
        $links = $this->getViewDifference($key, $value->getType(), $storage, $config_file);
        $class_name = 'color-error';

        if ($delta == '100') {
          $class_name = 'color-success';
        }
        $rows[] = [
          'name' => $config_file,
          'config' => [
            'class' => $class_name,
            'data' => $delta . '%',
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
    asort($rows);

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

  /**
   * Fetch the acquia cms profile with list of enabled modules of acms.
   */
  public function getAcquiaCmsProfileModuleList() {
    // Fetch acms profile and enabled modules which ships with acms.
    $profile_modules = $this->moduleHandler->getModuleList();
    $acms_profile_modules = array_filter($profile_modules, function ($key) {
      return strpos($key, 'acquia_cms') === 0;
    }, ARRAY_FILTER_USE_KEY);
    return $acms_profile_modules;
  }

  /**
   * Get the view difference link for each configuration.
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
        ]),
      ],
    ];
    return $links;
  }

  /**
   * Get the install/optional folder configuration.
   */
  public function getConfigList($path, $config_dir = 'install') {
    $storage = $this->getFileStorage($path);
    $config_list = $storage->listAll();
    $config_list = array_fill_keys($config_list, $config_dir);
    return $config_list;
  }

  /**
   * Get the file storage.
   */
  public function getFileStorage($path) {
    $file = new FileStorage($path);
    $storage = $this->importTransformer->transform($file);
    return $storage;
  }

  /**
   * Get the delta between Acquia CMS vs Active configuration.
   *
   * @param string $config_file
   *   Configuration file.
   * @param string $original_configuration_storage
   *   Original configuration storage.
   *
   * @return float
   *   Delta between configuration.
   */
  public function getDelta($config_file, $original_configuration_storage) {
    // Database configuration.
    $active_configuration = explode("\n", Yaml::encode($this->targetStorage->read($config_file)));
    // Configuration in files.
    $original_configuration = explode("\n", Yaml::encode($original_configuration_storage->read($config_file)));
    $active_configuration = array_values(array_filter(
      $active_configuration,
      function ($val, $key) use (&$active_configuration) {
        return (strpos($val, '_core') !== 0) && (strpos(trim($val), 'default_config_hash:') !== 0) && (strpos($val, 'uuid:') !== 0);
      },
      ARRAY_FILTER_USE_BOTH
    ));
    // Show configuration which present in both places.
    $diff = array_intersect($active_configuration, $original_configuration);
    // Count of config which present in both places vs count of database
    // configuration.
    // Active configuration have matches with Staged configuration.
    $percentage = round(count($diff) / count($active_configuration) * 100, 2);
    return $percentage;
  }

}
