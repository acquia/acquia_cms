<?php

namespace Drupal\acquia_cms_support\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\ImportStorageTransformer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * Defines a service which provides config sync for acquia cms.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
class AcquiaCmsConfigSyncService {

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
   * AcquiaCmsConfigSyncService constructor.
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
  public function __construct(
    ConfigFactoryInterface $config_factory,
    StorageInterface $target_storage,
    ImportStorageTransformer $import_transformer,
    ModuleHandlerInterface $module_handler
  ) {
    $this->configFactory = $config_factory;
    $this->targetStorage = $target_storage;
    $this->importTransformer = $import_transformer;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Fetch the acquia cms profile with list of enabled modules of ACMS.
   */
  public function getAcquiaCmsProfileModuleList() {
    $profile_modules = $this->moduleHandler->getModuleList();
    return array_filter($profile_modules, function ($key) {
      return str_starts_with($key, 'acquia_cms');
    }, ARRAY_FILTER_USE_KEY);
  }

  /**
   * Get the install/optional folder configuration.
   *
   * @param string $path
   *   The path.
   * @param string $config_dir
   *   The configuration directory.
   *
   * @return array
   *   The list of configurations
   *
   * @throws \Drupal\Core\Config\StorageTransformerException
   */
  public function getConfigList($path, $config_dir = 'install') {
    $storage = $this->getFileStorage($path);
    $config_list = $storage->listAll();
    $config_list = array_fill_keys($config_list, $config_dir);
    return $config_list;
  }

  /**
   * Get the file storage.
   *
   * @param string $path
   *   The path.
   *
   * @return \Drupal\Core\Config\DatabaseStorage|StorageInterface
   *   The file transform
   *
   * @throws \Drupal\Core\Config\StorageTransformerException
   *   The StorageTransformerException.
   */
  public function getFileStorage(string $path) {
    $file = new FileStorage($path);
    return $this->importTransformer->transform($file);
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
    return round(count($diff) / count($active_configuration) * 100, 0);
  }

}
