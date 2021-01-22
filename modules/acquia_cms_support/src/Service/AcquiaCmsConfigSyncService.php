<?php

namespace Drupal\acquia_cms_support\Service;

use Drupal\Component\Diff\Diff;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\ImportStorageTransformer;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageComparer;
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
   * Get the parity between Acquia CMS vs Active configuration.
   *
   * @param string $configFile
   *   Configuration file.
   * @param \Drupal\Core\Config\StorageInterface $syncStorage
   *   The storage to use as sync storage for compairing changes.
   *
   * @return float
   *   parity between configuration.
   */
  public function getParity($configFile, StorageInterface $syncStorage) {
    // Database configuration.
    $active_configuration = explode("\n", Yaml::encode($this->targetStorage->read($configFile)));
    // Configuration in files.
    $original_configuration = explode("\n", Yaml::encode($syncStorage->read($configFile)));
    $active_configuration = $this->removeNonRequiredKeys($active_configuration);
    $diff = new Diff($original_configuration, $active_configuration);
    $totalLines = count($active_configuration);
    $editedLines = 0;
    if (!$diff->isEmpty()) {
      foreach ($diff->getEdits() as $diffOp) {
        if ($diffOp->type !== 'copy') {
          $editedLines += $diffOp->nclosing();
        }
      }
    }
    return 100 - (int) round($editedLines / $totalLines * 100, 0);
  }

  /**
   * Remove _core, uuid, default_config_hash from configurations.
   *
   * @param array $data
   *   Configuration data.
   *
   * @return array
   *   Array of configurations after removing keys.
   */
  public function removeNonRequiredKeys(array $data) {
    // Remove the _core, uuid, default_config_hash from the configuration.
    $data = array_values(array_filter(
      $data,
      function ($val) use (&$data) {
        return (strpos($val, '_core') !== 0) && (strpos(trim($val), 'default_config_hash:') !== 0) && (strpos($val, 'uuid:') !== 0);
      },
      ARRAY_FILTER_USE_BOTH
    ));
    return $data;
  }

  /**
   * Get install config directory storage.
   *
   * @param string $path
   *   Path to use for install filestorage.
   *
   * @return object
   *   File Storage Object.
   */
  public function getInstallStorage($path) {
    return $this->getFileStorage($path . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY);
  }

  /**
   * Get optional config directory storage.
   *
   * @param string $path
   *   Path to use for optional filestorage.
   *
   * @return object
   *   File Storage Object.
   */
  public function getOptionalStorage($path) {
    return $this->getFileStorage($path . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY);
  }

  /**
   * Get the storage for a given path.
   *
   * @param string $path
   *   Path to use for filestorage.
   *
   * @return object
   *   FileStorage Object.
   */
  private function getFileStorage($path) {
    return new FileStorage($path);
  }

  /**
   * List all the changed (create and update) config from a storage.
   *
   * @param \Drupal\Core\Config\StorageInterface $syncStorage
   *   The storage to use as sync storage for compairing changes.
   *
   * @return array
   *   List of the chaged config.
   */
  public function getOverriddenConfig(StorageInterface $syncStorage) {
    $overriddenConfig = [];
    $storageComparer = $this->getStoragecomparer($syncStorage)->createChangelist();
    if ($storageComparer->hasChanges()) {
      $changedConfig = $this->getCreateAndUpdateChangeList($storageComparer);
      foreach ($changedConfig as $config) {
        $parity = $this->getParity($config, $syncStorage);
        if ($parity === 100) {
          continue;
        }
        $overriddenConfig[] = [
          'name' => $config,
          'parity' => $parity,
        ];
      }
    }
    return $overriddenConfig;
  }

  /**
   * Get Unchanged config list.
   *
   * @param \Drupal\Core\Config\StorageInterface $syncStorage
   *   The storage to use as sync storage for compairing changes.
   *
   * @return \Drupal\Core\Config\StorageComparer
   *   Storage Comparer object.
   */
  private function getStoragecomparer(StorageInterface $syncStorage) {
    return new StorageComparer($syncStorage, $this->targetStorage);
  }

  /**
   * Get changed config list.
   *
   * @param \Drupal\Core\Config\StorageComparer $storageComparer
   *   The storage to use as sync storage for compairing changes.
   *
   * @return array
   *   Array of changed configurations.
   */
  private function getCreateAndUpdateChangeList(StorageComparer $storageComparer) {
    $changedConfig = [];
    $createdConfig = $storageComparer->getChangelist('create');
    $updatedConfig = $storageComparer->getChangelist('update');
    $changedConfig = \array_merge($changedConfig, $createdConfig, $updatedConfig);
    return $changedConfig;
  }

  /**
   * Get Unchanged config list.
   *
   * @param \Drupal\Core\Config\StorageInterface $syncStorage
   *   The storage to use as sync storage for compairing changes.
   *
   * @return array
   *   Array of unchanged configurations.
   */
  public function getUnChangedConfig(StorageInterface $syncStorage) {
    $unChangedConfigList = [];
    $storageComparer = $this->getStoragecomparer($syncStorage)->createChangelist();
    if ($storageComparer->hasChanges()) {
      $changeList = $this->getCreateAndUpdateChangeList($storageComparer);
      foreach ($changeList as $config) {
        $parity = $this->getParity($config, $syncStorage);
        if ($parity !== 100) {
          continue;
        }
        \array_push($unChangedConfigList, $config);
      }
    }
    else {
      $unChangedConfigList = $syncStorage->listAll();
    }
    return $unChangedConfigList;
  }

}
