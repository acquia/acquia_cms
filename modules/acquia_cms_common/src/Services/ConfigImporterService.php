<?php

namespace Drupal\acquia_cms_common\Services;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigException;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\Error;
use Drush\Log\DrushLoggerManager;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * The ConfigImporter class to import drupal configurations.
 */
final class ConfigImporterService {

  /**
   * The config.manager service object.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The config.storage service object.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The cache.config service object.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $configCache;

  /**
   * The module_handler service object.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The event_dispatcher service object.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The lock service object.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The config.typed service object.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $configTyped;

  /**
   * The module_installer service object.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * The theme_handler service object.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The string_translation service object.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The extension.list.module service object.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Constructs the service.
   *
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   Holds config.manager service object.
   * @param \Drupal\Core\Config\StorageInterface $configStorage
   *   Holds config.storage service object.
   * @param \Drupal\Core\Cache\CacheBackendInterface $configCache
   *   Holds cache.config service object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Holds module_handler service object.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   Holds event_dispatcher service object.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   Holds lock service object.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $configTyped
   *   Holds config.typed service object.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller
   *   Holds module_installer service object.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   Holds theme_handler service object.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   Holds string_translation service object.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   Holds extension.list.module service object.
   */
  public function __construct(
    ConfigManagerInterface $configManager,
    StorageInterface $configStorage,
    CacheBackendInterface $configCache,
    ModuleHandlerInterface $moduleHandler,
    EventDispatcherInterface $eventDispatcher,
    LockBackendInterface $lock,
    TypedConfigManagerInterface $configTyped,
    ModuleInstallerInterface $moduleInstaller,
    ThemeHandlerInterface $themeHandler,
    TranslationInterface $stringTranslation,
    ModuleExtensionList $moduleExtensionList
  ) {
    $this->configManager = $configManager;
    $this->configStorage = $configStorage;
    $this->configCache = $configCache;
    $this->moduleHandler = $moduleHandler;
    $this->eventDispatcher = $eventDispatcher;
    $this->lock = $lock;
    $this->configTyped = $configTyped;
    $this->moduleInstaller = $moduleInstaller;
    $this->themeHandler = $themeHandler;
    $this->stringTranslation = $stringTranslation;
    $this->moduleExtensionList = $moduleExtensionList;
  }

  /**
   * Imports the configurations.
   *
   * Copied from vendor/drush/drush/src/Commands/config/ConfigImportCommands.
   *
   * @throws \Exception
   */
  public function doImport(StorageComparer $storage_comparer, DrushLoggerManager $loggerManager): void {
    $config_importer = new ConfigImporter(
      $storage_comparer,
      $this->eventDispatcher,
      $this->configManager,
      $this->lock,
      $this->configTyped,
      $this->moduleHandler,
      $this->moduleInstaller,
      $this->themeHandler,
      $this->stringTranslation,
      $this->moduleExtensionList
    );
    if ($config_importer->alreadyImporting()) {
      $loggerManager->warning('Another request may be synchronizing configuration already.');
    }
    else {
      try {
        // This is the contents of \Drupal\Core\Config\ConfigImporter::import.
        // Copied here so we can log progress.
        if ($config_importer->hasUnprocessedConfigurationChanges()) {
          $sync_steps = $config_importer->initialize();
          foreach ($sync_steps as $step) {
            $context = [];
            do {
              $config_importer->doSyncStep($step, $context);
              if (isset($context['message'])) {
                $loggerManager->notice(str_replace('Synchronizing', 'Synchronized', (string) $context['message']));
              }
            } while ($context['finished'] < 1);
          }
          // Clear the cache of the active config storage.
          $this->configCache->deleteAll();
        }
        if ($config_importer->getErrors()) {
          throw new ConfigException('Errors occurred during import');
        }
        else {
          $loggerManager->success('The configuration was imported successfully.');
        }
      }
      catch (ConfigException $e) {
        // Return a negative result for UI purposes. We do not differentiate
        // between an actual synchronization error and a failed lock, because
        // concurrent synchronizations are an edge-case happening only when
        // multiple developers or site builders attempt to do it without
        // coordinating.
        $message = 'The import failed due to the following reasons:' . "\n";
        $message .= implode("\n", $config_importer->getErrors());

        Error::logException('config_import', $e);
        throw new \Exception($message);
      }
    }
  }

}
