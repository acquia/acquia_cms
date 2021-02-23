<?php

namespace Drupal\acquia_cms_common\Commands;

use Consolidation\AnnotatedCommand\CommandError;
use Drupal\acquia_cms\Facade\CohesionFacade;
use Drupal\Component\Serialization\Yaml;
use Drupal\config\StorageReplaceDataWrapper;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigException;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drush\Commands\DrushCommands;
use Drush\Drupal\Commands\config\ConfigCommands;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * A Drush command file.
 *
 * This files contains custom drush command to provide a way to import
 * standard configuration with partial option along with site studio package
 * for particular some or all given modules.
 */
class AcmsConfigImportCommands extends DrushCommands {

  /**
   * The ConfigManagerInterface.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  protected $configStorage;

  protected $configStorageSync;

  protected $configCache;

  protected $eventDispatcher;

  protected $lock;

  protected $configTyped;

  protected $moduleInstaller;

  protected $themeHandler;

  protected $stringTranslation;

  protected $importStorageTransformer;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * @return \Drupal\Core\Config\ConfigManagerInterface
   */
  public function getConfigManager() {
    return $this->configManager;
  }

  /**
   * @return \Drupal\Core\Config\StorageInterface
   */
  public function getConfigStorage() {
    return $this->configStorage;
  }

  /**
   * @return \Drupal\Core\Config\StorageInterface
   */
  public function getConfigStorageSync() {
    return $this->configStorageSync;
  }

  /**
   * @return \Drupal\Core\Cache\CacheBackendInterface
   */
  public function getConfigCache() {
    return $this->configCache;
  }

  /**
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public function getModuleHandler() {
    return $this->moduleHandler;
  }

  /**
   * Note that type hint is changing https://www.drupal.org/project/drupal/issues/3161983.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  public function getEventDispatcher() {
    return $this->eventDispatcher;
  }

  /**
   * @return \Drupal\Core\Lock\LockBackendInterface
   */
  public function getLock() {
    return $this->lock;
  }

  /**
   * @return \Drupal\Core\Config\TypedConfigManagerInterface
   */
  public function getConfigTyped() {
    return $this->configTyped;
  }

  /**
   * @return \Drupal\Core\Extension\ModuleInstallerInterface
   */
  public function getModuleInstaller() {
    return $this->moduleInstaller;
  }

  /**
   * @return \Drupal\Core\Extension\ThemeHandlerInterface
   */
  public function getThemeHandler() {
    return $this->themeHandler;
  }

  /**
   * @return \Drupal\Core\StringTranslation\TranslationInterface
   */
  public function getStringTranslation() {
    return $this->stringTranslation;
  }

  /**
   * @param \Drupal\Core\Config\ImportStorageTransformer $importStorageTransformer
   */
  public function setImportTransformer($importStorageTransformer) {
    $this->importStorageTransformer = $importStorageTransformer;
  }

  /**
   * @return bool
   */
  public function hasImportTransformer() {
    return isset($this->importStorageTransformer);
  }

  /**
   * @return \Drupal\Core\Config\ImportStorageTransformer
   */
  public function getImportTransformer() {
    return $this->importStorageTransformer;
  }

  /**
   * @return \Drupal\Core\Extension\ModuleExtensionList
   */
  public function getModuleExtensionList(): ModuleExtensionList {
    return $this->moduleExtensionList;
  }

  /**
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   * @param \Drupal\Core\Config\StorageInterface $configStorage
   * @param \Drupal\Core\Config\StorageInterface $configStorageSync
   * @param \Drupal\Core\Cache\CacheBackendInterface $configCache
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   * @param $eventDispatcher
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $configTyped
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   */
  public function __construct(
        ConfigManagerInterface $configManager,
        StorageInterface $configStorage,
        StorageInterface $configStorageSync,
        CacheBackendInterface $configCache,
        ModuleHandlerInterface $moduleHandler,
        // Omit type hint as it changed in https://www.drupal.org/project/drupal/issues/3161983
        $eventDispatcher,
        LockBackendInterface $lock,
        TypedConfigManagerInterface $configTyped,
        ModuleInstallerInterface $moduleInstaller,
        ThemeHandlerInterface $themeHandler,
        TranslationInterface $stringTranslation,
        ModuleExtensionList $moduleExtensionList
    ) {
    parent::__construct();
    $this->configManager = $configManager;
    $this->configStorage = $configStorage;
    $this->configStorageSync = $configStorageSync;
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
   * Reset configurations to default.
   *
   * Command to reset configuration for ACMS modules
   * to the default canonical config, as exported in code.
   *
   * @param array $package
   *   The name of modules separated by space.
   * @param array $options
   *   The options array.
   *
   * @throws \Drush\Exceptions\UserAbortException
   *
   * @option scope
   *   The scope for particular package to be imported.
   * @command acms:config-reset
   * @aliases acr
   * @usage acms:config-reset
   *   Reset the configuration to the default.
   * @usage acms:config-reset acquia_cms_article acquia_cms_common --scope=all
   *   Reset the configuration to the default.
   */
  public function resetConfigurations(array $package, array $options = ['scope' => NULL]) {
    $this->io()->text(["Welcome to Acquia CMS's Config reset wizard.",
      "This should only be used in case of emergencies and can lead to unexpected impacts on the site.",
    ]);
    // Get the acquia cms module list.
    $scope_allowed = ['config', 'site-studio', 'all'];
    $modules = $this->moduleHandler->getModuleList();
    $modules_array = [];
    foreach ($modules as $module => $module_obj) {
      if ($module_obj->getType() === 'module' && str_starts_with($module_obj->getName(), 'acquia_cms')) {
        $modules_array[] = $module;
      }
    }

    // Lets get input from user if not provided package with command.
    if (empty($package)) {
      if (!empty($modules_array)) {
        $question_string = 'Choose a Module to reset its configurations. Separate multiple choices with commas, e.g. "1,2,4".';
        $choices = array_merge(['Cancel'], $modules_array);
        array_push($choices, 'All');
        $question = new ChoiceQuestion(dt($question_string), $choices, NULL);
        $question->setMultiselect(TRUE);
        $types = $this->io()->askQuestion($question);
        if (in_array('Cancel', $types)) {
          throw new UserAbortException();
        }
        elseif (in_array('All', $types)) {
          $package = $modules_array;
        }
        else {
          $package = $types;
        }
        // Lets ask for scope if not already provided.
        if (!$options['scope']) {
          $scope = $this->io()->choice(dt('Choose a scope.'), $scope_allowed, NULL);
          $options['scope'] = $scope_allowed[$scope];
        }
        elseif ($options['scope'] && !in_array($options['scope'], $scope_allowed)) {
          throw new \InvalidArgumentException('Invalid scope, allowed values are [config, site-studio, all]');
        }
        // Lets import the configurations.
        $this->doImport($package, $options['scope']);
      }
    }
    // Lets check provided package & scope are valid.
    else {
      foreach ($package as $module) {
        if (!in_array($module, $modules_array)) {
          throw new \InvalidArgumentException('Invalid module name:' . $module);
        }
      }
      if (!isset($options['scope'])) {
        throw new \InvalidArgumentException('Missing argument scope');
      }
      if ($options['scope'] && !in_array($options['scope'], $scope_allowed)) {
        throw new \InvalidArgumentException('Invalid scope, allowed values are [config, site-studio, all]');
      }

      // Lets import the configurations.
      $this->doImport($package, $options['scope']);
    }
  }

  /**
   * Import configuration based on scope.
   *
   * @param array $package
   *   The array of modules.
   * @param string $scope
   *   The scope.
   *
   * @throws \Drush\Exceptions\UserAbortException
   * @throws \Exception
   */
  protected function doImport(array $package, string $scope) {
    $config_files = $ss_config_files = [];
    if (in_array($scope, ['config', 'all'])) {
      foreach ($package as $module) {
        $config_files = array_merge($config_files, $this->getConfigFiles($module));
      }
      $this->importPartialConfig($config_files);
    }
    // Import the site studio configurations.
    if (in_array($scope, ['site-studio', 'all'])) {
      // Show big warning if site-studio is in scope.
      $this->io()->warning("This can have unintended side effects for existing pages built using previous versions of components, it might literally break them, and should be tested in a non-production environment first.");
      foreach ($package as $module) {
        $ss_config_files = array_merge($ss_config_files, $this->getSiteStudioPackage($module));
      }
      $this->importSiteStudioPackage($ss_config_files);
    }
  }

  /**
   * Import configurations for the given module & its scope.
   *
   * @param string $module
   *   The name of module.
   *
   * @return array
   */
  protected function getConfigFiles(string $module) {
    $config_files = [];
    $sources[] = drupal_get_path('module', $module) . '/config/install';
    $sources[] = drupal_get_path('module', $module) . '/config/optional';
    foreach ($sources as $source) {
      $source_storage_dir = ConfigCommands::getDirectory(NULL, $source);
      $source_storage = new FileStorage($source_storage_dir);

      foreach ($source_storage->listAll() as $name) {
        $config_files[$name] = $source_storage->read($name);
      }
    }
    return $config_files;
  }

  /**
   * Get the site studio package for given module.
   *
   * @param string $module
   *   The name of module.
   *
   * @return array|string[]
   */
  protected function getSiteStudioPackage(string $module) {
    $dir = drupal_get_path('module', $module);
    $list = "$dir/config/dx8/packages.yml";
    if (file_exists($list)) {
      $list = file_get_contents($list);

      $map = function (string $package) use ($dir) {
        return "$dir/$package";
      };
      return array_map($map, Yaml::decode($list));
    }
    return [];
  }

  /**
   * Get all required operations to import site studio packages.
   *
   * @param array $packages
   *   The packages to import.
   */
  public function importSiteStudioPackage(array $packages) {
    /** @var \Drupal\acquia_cms\Facade\CohesionFacade $facade */
    $facade = \Drupal::classResolver(CohesionFacade::class);
    $operations = [];
    foreach ($packages as $package) {
      $operations = array_merge($operations, $facade->importPackage($package, TRUE));
    }
    $batch = [
      'title' => t('Importing configuration.'),
      'operations' => $operations,
      'finished' => '\Drupal\acquia_cms\Facade\CohesionFacade::batchFinishedCallback',
    ];
    batch_set($batch);
  }

  /**
   * Import configurations for the given sources.
   *
   * @param array $config_files
   *   The config file that needs re-import.
   *
   * @return bool|CommandError|mixed|void
   *
   * @throws \Drush\Exceptions\UserAbortException
   */
  protected function importPartialConfig(array $config_files) {
    // Since we are running config import with partial option
    // Lets check config module is enabled or not.
    if (!\Drupal::moduleHandler()->moduleExists('config')) {
      return new CommandError('Config module is not enabled, please enable it.');
    }
    // Determine $source_storage in partial case.
    $active_storage = $this->getConfigStorage();

    $replacement_storage = new StorageReplaceDataWrapper($active_storage);
    foreach ($config_files as $name => $data) {
      $replacement_storage->replaceData($name, $data);
    }
    $source_storage = $replacement_storage;

    $config_manager = $this->getConfigManager();
    $storage_comparer = new StorageComparer($source_storage, $active_storage, $config_manager);
    if (!$storage_comparer->createChangelist()->hasChanges()) {
      $this->logger()->notice(('There are no changes to import.'));
      return;
    }

    // List the changes in table format.
    $change_list = [];
    foreach ($storage_comparer->getAllCollectionNames() as $collection) {
      $change_list[$collection] = $storage_comparer->getChangelist(NULL, $collection);
    }
    $table = ConfigCommands::configChangesTable($change_list, $this->output());
    $table->render();

    if (!$this->io()->confirm(dt('Import the listed configuration changes?'))) {
      throw new UserAbortException();
    }
    return drush_op([$this, 'runImport'], $storage_comparer);
  }

  /**
   * Run the import process for configurations.
   */
  public function runImport($storage_comparer) {
    $config_importer = new ConfigImporter(
      $storage_comparer,
      $this->getEventDispatcher(),
      $this->getConfigManager(),
      $this->getLock(),
      $this->getConfigTyped(),
      $this->getModuleHandler(),
      $this->getModuleInstaller(),
      $this->getThemeHandler(),
      $this->getStringTranslation(),
      $this->getModuleExtensionList()
    );
    if ($config_importer->alreadyImporting()) {
      $this->logger()->warning('Another request may be synchronizing configuration already.');
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
                $this->logger()->notice(str_replace('Synchronizing', 'Synchronized', (string) $context['message']));
              }
            } while ($context['finished'] < 1);
          }
          // Clear the cache of the active config storage.
          $this->getConfigCache()->deleteAll();
        }
        if ($config_importer->getErrors()) {
          throw new ConfigException('Errors occurred during import');
        }
        else {
          $this->logger()->success('The configuration was imported successfully.');
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

        watchdog_exception('acms_config_import', $e);
        throw new \Exception($message);
      }
    }
  }

}
