<?php

namespace Drupal\acquia_cms_common\Commands;

use Consolidation\AnnotatedCommand\CommandData;
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
use Drupal\Core\DependencyInjection\ClassResolver;
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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A Drush command file.
 *
 * This files contains custom drush command to provide a way to import
 * standard configuration with partial option along with site studio package
 * for particular some or all given modules.
 */
final class AcmsConfigImportCommands extends DrushCommands {

  /**
   * The allowed scope.
   *
   * @var string[]
   * Allowed scope for drush commands.
   */
  const ALLOWED_SCOPE = ['config', 'site-studio', 'all'];

  /**
   * The config manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The StorageInterface object.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The config storage sync.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorageSync;

  /**
   * The config cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $configCache;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The lock.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The config type.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $configTyped;

  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * The theme installer.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The string translation interface.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The import storage.
   *
   * @var \Drupal\Core\Config\ImportStorageTransformer
   */
  protected $importStorageTransformer;

  /**
   * The module handler.
   *
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
   * The ClassResolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolver
   */
  protected $classResolver;

  /**
   * Get configuration manager.
   *
   * @return \Drupal\Core\Config\ConfigManagerInterface
   *   The ConfigManagerInterface.
   */
  public function getConfigManager() {
    return $this->configManager;
  }

  /**
   * Get config storage object.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The StorageInterface.
   */
  public function getConfigStorage() {
    return $this->configStorage;
  }

  /**
   * Get config storage sync object.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The StorageInterface.
   */
  public function getConfigStorageSync() {
    return $this->configStorageSync;
  }

  /**
   * Get config cache object.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The CacheBackendInterface.
   */
  public function getConfigCache() {
    return $this->configCache;
  }

  /**
   * Get module handler object.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The ModuleHandlerInterface.
   */
  public function getModuleHandler() {
    return $this->moduleHandler;
  }

  /**
   * Note that type hint is changing.
   *
   * Refer:
   * https://www.drupal.org/project/drupal/issues/3161983.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   *   The EventDispatcherInterface.
   */
  public function getEventDispatcher() {
    return $this->eventDispatcher;
  }

  /**
   * Get the lock object.
   *
   * @return \Drupal\Core\Lock\LockBackendInterface
   *   The LockBackendInterface.
   */
  public function getLock() {
    return $this->lock;
  }

  /**
   * Get config type object.
   *
   * @return \Drupal\Core\Config\TypedConfigManagerInterface
   *   The TypedConfigManagerInterface.
   */
  public function getConfigTyped() {
    return $this->configTyped;
  }

  /**
   * Get module installer object.
   *
   * @return \Drupal\Core\Extension\ModuleInstallerInterface
   *   The ModuleInstallerInterface.
   */
  public function getModuleInstaller() {
    return $this->moduleInstaller;
  }

  /**
   * Get theme handler object.
   *
   * @return \Drupal\Core\Extension\ThemeHandlerInterface
   *   The ThemeHandlerInterface.
   */
  public function getThemeHandler() {
    return $this->themeHandler;
  }

  /**
   * Get string translation object.
   *
   * @return \Drupal\Core\StringTranslation\TranslationInterface
   *   The TranslationInterface.
   */
  public function getStringTranslation() {
    return $this->stringTranslation;
  }

  /**
   * Set import transformer.
   *
   * @param ImportStorageTransformer $importStorageTransformer
   *   The ImportStorageTransformer.
   */
  public function setImportTransformer(ImportStorageTransformer $importStorageTransformer) {
    $this->importStorageTransformer = $importStorageTransformer;
  }

  /**
   * Check import transformer.
   *
   * @return bool
   *   The boolean true,false.
   */
  public function hasImportTransformer() {
    return isset($this->importStorageTransformer);
  }

  /**
   * Get import storage transformer.
   *
   * @return \Drupal\Core\Config\ImportStorageTransformer
   *   The ImportStorageTransformer.
   */
  public function getImportTransformer() {
    return $this->importStorageTransformer;
  }

  /**
   * Get list of modules.
   *
   * @return \Drupal\Core\Extension\ModuleExtensionList
   *   The ModuleExtensionList.
   */
  public function getModuleExtensionList(): ModuleExtensionList {
    return $this->moduleExtensionList;
  }

  /**
   * The class constructor.
   *
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   The ConfigManagerInterface.
   * @param \Drupal\Core\Config\StorageInterface $configStorage
   *   The StorageInterface.
   * @param \Drupal\Core\Config\StorageInterface $configStorageSync
   *   The StorageInterface.
   * @param \Drupal\Core\Cache\CacheBackendInterface $configCache
   *   The CacheBackendInterface.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The ModuleHandlerInterface.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $configTyped
   *   The TypedConfigManagerInterface.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The TranslationInterface.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The ModuleExtensionList.
   * @param \Drupal\Core\DependencyInjection\ClassResolver $classResolver
   *   The class resolver.
   */
  public function __construct(
    ConfigManagerInterface $configManager,
    StorageInterface $configStorage,
    StorageInterface $configStorageSync,
    CacheBackendInterface $configCache,
    ModuleHandlerInterface $moduleHandler,
    // Omit type hint as it changed in
    // https://www.drupal.org/project/drupal/issues/3161983
    EventDispatcherInterface $eventDispatcher,
    LockBackendInterface $lock,
    TypedConfigManagerInterface $configTyped,
    ModuleInstallerInterface $moduleInstaller,
    ThemeHandlerInterface $themeHandler,
    TranslationInterface $stringTranslation,
    ModuleExtensionList $moduleExtensionList,
    ClassResolver $classResolver
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
    $this->classResolver = $classResolver;

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
    // Lets get input from user if not provided package with command.
    if (empty($package)) {
      $acms_modules = $this->getAcquiaModuleList();
      $question_string = 'Choose a module to reset configurations. Separate multiple choices with commas, e.g. "1,2,4".';
      $question = $this->createMultipleChoiceOptions($question_string, $acms_modules);
      $types = $this->io()->askQuestion($question);
      if (in_array('Cancel', $types)) {
        throw new UserAbortException();
      }
      elseif (in_array('All', $types)) {
        $package = $acms_modules;
      }
      else {
        $package = $types;
      }
      // Lets ask for scope if not already provided.
      if (!$options['scope']) {
        $scope = $this->io()->choice(dt('Choose a scope.'), self::ALLOWED_SCOPE, NULL);
        $options['scope'] = self::ALLOWED_SCOPE[$scope];
      }
      elseif ($options['scope'] && !in_array($options['scope'], self::ALLOWED_SCOPE)) {
        throw new \InvalidArgumentException('Invalid scope, allowed values are [config, site-studio, all]');
      }
    }
    // Lets import the configurations.
    $this->doImport($package, $options['scope']);
  }

  /**
   * Create multiple choice question.
   *
   * @param string $question_string
   *   The question to ask.
   * @param array $choice_options
   *   The choice of options.
   * @param int|null $default
   *   The default option in multi-choice.
   *
   * @return \Symfony\Component\Console\Question\ChoiceQuestion
   *   The ChoiceQuestion
   */
  private function createMultipleChoiceOptions(string $question_string, array $choice_options, $default = NULL) {
    $choices = array_merge(['Cancel'], $choice_options);
    array_push($choices, 'All');
    $question = new ChoiceQuestion(dt($question_string), $choices, $default);
    $question->setMultiselect(TRUE);
    return $question;
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
  private function doImport(array $package, string $scope) {
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
   *   The array of config files.
   */
  private function getConfigFiles(string $module): array {
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
   * @return array
   *   The array of site studio package.
   */
  private function getSiteStudioPackage(string $module): array {
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
   *
   * @throws \Exception
   */
  private function importSiteStudioPackage(array $packages) {
    $cohesion_facade = $this->classResolver->getInstanceFromDefinition(CohesionFacade::class);
    $operations = [];
    foreach ($packages as $package) {
      $operations = array_merge($operations, $cohesion_facade->importPackage($package, TRUE));
    }
    $batch = [
      'title' => $this->stringTranslation->translate('Importing configuration.'),
      'operations' => $operations,
      'finished' => '\Drupal\acquia_cms\Facade\CohesionFacade::batchFinishedCallback',
    ];
    batch_set($batch);
    drush_backend_batch_process();
  }

  /**
   * Import configurations for the given sources.
   *
   * @param array $config_files
   *   The config file that needs re-import.
   *
   * @return \Consolidation\AnnotatedCommand\CommandError|mixed|void
   *   The result command.
   *
   * @throws \Drush\Exceptions\UserAbortException
   */
  private function importPartialConfig(array $config_files) {
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
      exit();
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
   *
   * @param \Drupal\Core\Config\StorageComparer $storage_comparer
   *   The storage comparer.
   *
   * @throws \Exception
   */
  public function runImport(StorageComparer $storage_comparer) {
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

  /**
   * Hook validate for acms config reset command.
   *
   * @hook validate acms:config-reset
   */
  public function validate(CommandData $commandData) {
    // Since we are running config import with partial option
    // Lets check config module is enabled or not.
    if (!$this->moduleHandler->moduleExists('config')) {
      $messages[] = 'Config module is not enabled, please enable it.';
    }

    $messages = [];
    $isInteractive = $commandData->input()->isInteractive();
    $scope = $commandData->input()->getOption('scope');
    $package = $commandData->input()->getArgument('package');
    if (isset($scope) && !in_array($scope, self::ALLOWED_SCOPE)) {
      $messages[] = 'Invalid scope, allowed values are [config, site-studio, all]';
    }
    if ($package && !$this->hasValidPackage($package)) {
      $messages[] = 'Given package are not valid, try providing list of ACMS modules ex: acquia_cms_article';
    }
    // In case of -y lets check user has provided all the required arguments.
    if (!$isInteractive && (!$package || !$scope)) {
      $messages[] = 'In order to use -y option, please provide package and scope variable.';
    }
    if ($messages) {
      return new CommandError(implode(' ', $messages));
    }
  }

  /**
   * Check provided package are valid one.
   *
   * @param array $packages
   *   The array of package.
   *
   * @return bool
   *   The status of package.
   */
  private function hasValidPackage(array $packages): bool {
    $valid_package = $this->getAcquiaModuleList();
    foreach ($packages as $package) {
      if (!in_array($package, $valid_package)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Fetch the list of enabled modules of ACMS.
   */
  private function getAcquiaModuleList(): array {
    $modules = $this->moduleHandler->getModuleList();
    $acms_modules = [];
    foreach ($modules as $module => $module_obj) {
      if ($module_obj->getType() === 'module' && str_starts_with($module_obj->getName(), 'acquia_cms')) {
        $acms_modules[] = $module;
      }
    }
    return $acms_modules;
  }

}
