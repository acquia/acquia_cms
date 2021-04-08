<?php

namespace Drupal\acquia_cms_common\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandError;
use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\acquia_cms\Facade\CohesionFacade;
use Drupal\acquia_cms_common\Services\AcmsUtilityService;
use Drupal\cohesion\Drush\DX8CommandHelpers;
use Drupal\Component\Serialization\Yaml;
use Drupal\config\StorageReplaceDataWrapper;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drush\Commands\DrushCommands;
use Drush\Drupal\Commands\config\ConfigCommands;
use Drush\Drupal\Commands\config\ConfigImportCommands;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\Console\Question\ChoiceQuestion;

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
   * The standard drush config import commands.
   *
   * @var \Drush\Drupal\Commands\config\ConfigImportCommands
   */
  protected $configImportCommands;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The string translation interface.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The ClassResolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolver
   */
  protected $cohesionFacade;

  /**
   * The acquia cms utility service.
   *
   * @var \Drupal\acquia_cms_common\Services\AcmsUtilityService
   */
  protected $acmsUtilityService;

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
   * Get string translation object.
   *
   * @return \Drupal\Core\StringTranslation\TranslationInterface
   *   The TranslationInterface.
   */
  public function getStringTranslation() {
    return $this->stringTranslation;
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
   * The class constructor.
   *
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   The ConfigManagerInterface.
   * @param \Drupal\Core\Config\StorageInterface $configStorage
   *   The StorageInterface.
   * @param \Drush\Drupal\Commands\config\ConfigImportCommands $configImportCommands
   *   The config importer class.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The TranslationInterface.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The ModuleHandlerInterface.
   * @param \Drupal\Core\DependencyInjection\ClassResolver $classResolver
   *   The class resolver.
   * @param \Drupal\acquia_cms_common\Services\AcmsUtilityService $acmsUtilityService
   *   The acquia cms service.
   */
  public function __construct(
    ConfigManagerInterface $configManager,
    StorageInterface $configStorage,
    ConfigImportCommands $configImportCommands,
    TranslationInterface $stringTranslation,
    ModuleHandlerInterface $moduleHandler,
    ClassResolver $classResolver,
    AcmsUtilityService $acmsUtilityService
    ) {
    parent::__construct();
    $this->configManager = $configManager;
    $this->configStorage = $configStorage;
    $this->configImportCommands = $configImportCommands;
    $this->stringTranslation = $stringTranslation;
    $this->moduleHandler = $moduleHandler;
    $this->cohesionFacade = $classResolver->getInstanceFromDefinition(CohesionFacade::class);
    ;
    $this->acmsUtilityService = $acmsUtilityService;
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
   * @option scope
   *   The scope for particular package to be imported.
   * @option delete-list
   *   The comma separated list of config files to be deleted during import.
   * @command acms:config-reset
   * @aliases acr
   * @usage acms:config-reset
   *   Reset the configuration to the default.
   * @usage acms:config-reset acquia_cms_article acquia_cms_article acquia_cms_person --scope=all
   * --delete-list=search_api.index.acquia_search_index
   *   Reset the configuration to the default.
   *
   * @throws \Drush\Exceptions\UserAbortException
   */
  public function resetConfigurations(array $package, array $options = [
    'scope' => NULL,
    'delete-list' => NULL,
  ]) {
    $this->io()->text(["Welcome to the Acquia CMS config reset wizard.",
      "This should be used with extreme caution and can lead to unexpected behavior on your site if not well tested.",
      "Do not run this in production until you've tested it in a safe, non-public environment first.",
    ]);
    // Reset the configurations for given packages aka modules
    // package, scope & delete-list are being added in validate command.
    $this->doImport($package, $options['scope'], $options['delete-list']);
  }

  /**
   * Get package from user input if not provided already.
   *
   * @return array
   *   The package from user input.
   *
   * @throws \Drush\Exceptions\UserAbortException
   */
  private function getPackagesFromUserInput(): array {
    // Lets get input from user if not provided package with command.
    $acms_modules = $this->getAcmsModules();
    $question_string = 'Choose a module that needs a configuration reset. Separate multiple choices with commas, e.g. "1,2,4".';
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
    return $package;
  }

  /**
   * Get list of Acquia CMS modules.
   *
   * @return array
   *   Array of acms modules.
   */
  private function getAcmsModules(): array {
    // Start with the profile itself.
    $acms_modules = ['acquia_cms'];
    $acms_extensions = $this->acmsUtilityService->getAcquiaCmsProfileModuleList();
    foreach ($acms_extensions as $key => $module) {
      if ($module->getType() === 'module') {
        $acms_modules[] = $key;
      }
    }
    return $acms_modules;
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
  private function createMultipleChoiceOptions(string $question_string, array $choice_options, $default = NULL): ChoiceQuestion {
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
   * @param array $delete_list
   *   The list of config files to be deleted during import.
   *
   * @throws \Drush\Exceptions\UserAbortException
   * @throws \Exception
   */
  private function doImport(array $package, string $scope, array $delete_list) {
    $config_files = $ss_config_files = [];
    if (in_array($scope, ['config', 'all'])) {
      foreach ($package as $module) {
        $config_files = array_merge($config_files, $this->getConfigFiles($module));
      }
      // Validate delete list against given scope of configurations.
      if (!$this->validDeleteList($config_files, $delete_list)) {
        throw new \Exception("The file specified in --delete-list option is invalid.");
      }
      $this->importPartialConfig($config_files, $delete_list);
    }

    // Build site studio packages.
    if (in_array($scope, ['site-studio', 'all'])) {
      foreach ($package as $module) {
        $ss_config_files = array_merge($ss_config_files, $this->getSiteStudioPackage($module));
      }
      // Confirm the site studio changes before import.
      if ($this->buildSiteStudioChangeList($ss_config_files)) {
        if (!$this->io()->confirm(dt('Import these site studio configuration changes?'))) {
          throw new UserAbortException();
        }
        // Import the site studio configurations.
        $this->importSiteStudioPackage($ss_config_files);
      }
      else {
        $this->io()->success('No site studio package to import.');
      }
    }
  }

  /**
   * Show change list for site studio packages.
   *
   * @param array $ss_config_files
   *   Array of configurations file.
   *
   * @return bool
   *   The package status.
   */
  private function buildSiteStudioChangeList(array $ss_config_files): bool {
    if (empty($ss_config_files)) {
      return FALSE;
    }
    $rows = [];
    foreach ($ss_config_files as $name) {
      $rows[] = [$name];
    }
    // Show warning if site-studio is in scope.
    $this->io()->warning("This can have unintended side effects for existing pages built using previous versions of components, it might literally break them, and should be tested in a non-production environment first.");
    $this->io()->table(['Configuration'], $rows);
    return TRUE;
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
    $source_install = drupal_get_path('module', $module) . '/config/install';
    $source_optional = drupal_get_path('module', $module) . '/config/optional';

    // Get optional configuration list for specified module.
    $source_storage_dir = ConfigCommands::getDirectory(NULL, $source_optional);
    $source_storage = new FileStorage($source_storage_dir);
    foreach ($source_storage->listAll() as $name) {
      $config_files[$name] = $source_storage->read($name);
    }
    // Remove configuration files where its dependencies cannot be met
    // in case of optional configurations.
    $this->removeDependentFiles($config_files);

    // Now get default configurations.
    $source_storage_dir = ConfigCommands::getDirectory(NULL, $source_install);
    $source_storage = new FileStorage($source_storage_dir);
    foreach ($source_storage->listAll() as $name) {
      $config_files[$name] = $source_storage->read($name);
    }

    return $config_files;
  }

  /**
   * Remove configuration files where its dependencies cannot be met.
   *
   * @param array $config_files
   *   Array of configurations files.
   *
   * @return array
   *   Array of filtered configurations file.
   */
  private function removeDependentFiles(array &$config_files): array {
    $enabled_extensions = $this->acmsUtilityService->getEnabledExtensions();
    $all_config = $active_storage = $this->getConfigStorage()->listAll();
    $all_config = array_combine($all_config, $all_config);
    foreach ($config_files as $config_name => $data) {
      // Remove configuration where its dependencies cannot be met.
      $remove = !$this->acmsUtilityService->validateDependencies($config_name, $data, $enabled_extensions, $all_config);
      if ($remove) {
        unset($config_files[$config_name]);
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
    $operations = [];
    foreach ($packages as $package) {
      $operations = array_merge($operations, $this->cohesionFacade->importPackage($package));
    }
    $batch = ['operations' => $operations];
    batch_set($batch);
    drush_backend_batch_process();
  }

  /**
   * Import configurations for the given sources.
   *
   * @param array $config_files
   *   The config file that needs re-import.
   * @param array $delete_list
   *   The list of configurations to be deleted before import.
   *
   * @throws \Drush\Exceptions\UserAbortException
   * @throws \Exception
   */
  private function importPartialConfig(array $config_files, array $delete_list) {
    // Determine $source_storage in partial case.
    $active_storage = $this->getConfigStorage();

    $replacement_storage = new StorageReplaceDataWrapper($active_storage);
    foreach ($config_files as $name => $data) {
      $replacement_storage->replaceData($name, $data);
    }
    $source_storage = $replacement_storage;
    // In case of --delete-list option lets delete configurations from
    // source storage before running the actual importing.
    if ($delete_list) {
      foreach ($delete_list as $del_config_item) {
        // Allow for accidental .yml extension.
        if (substr($del_config_item, -4) === '.yml') {
          $del_config_item = substr($del_config_item, 0, -4);
        }
        if ($source_storage->exists($del_config_item)) {
          $source_storage->delete($del_config_item);
        }
      }
    }
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

    if (!$this->io()->confirm(dt('Import these configuration changes?'))) {
      throw new UserAbortException();
    }
    $this->configImportCommands->doImport($storage_comparer);
  }

  /**
   * Hook validate for acms config reset command.
   *
   * @hook validate acms:config-reset
   *
   * @throws \Drush\Exceptions\UserAbortException
   */
  public function validateConfigResetCommand(CommandData $commandData) {
    // Since we are running config import with partial option
    // Lets check config module is enabled or not.
    if (!$this->moduleHandler->moduleExists('config')) {
      return new CommandError('Config module is not enabled, please enable it.');
    }

    $messages = [];
    $isInteractive = $commandData->input()->isInteractive();
    $scope = $commandData->input()->getOption('scope');
    $delete_list = $commandData->input()->getOption('delete-list');
    $package = $commandData->input()->getArgument('package');

    if (isset($scope) && !in_array($scope, self::ALLOWED_SCOPE)) {
      $messages[] = 'Invalid scope, allowed values are [config, site-studio, all]';
    }
    if ($package && !$this->hasValidPackage($package)) {
      $messages[] = 'Given packages are not valid, try providing a list of ACMS modules separated by space ex: acquia_cms_article acquia_cms_place';
    }
    // In case of --delete-list option.
    if ($delete_list) {
      $delete_list_array = array_filter(explode(',', $delete_list));
      if (empty($delete_list_array)) {
        $messages[] = dt("The file specified in --delete-list option is in the wrong format.");
      }
      else {
        $commandData->input()->setOption('delete-list', $delete_list_array);
      }
    }
    else {
      $commandData->input()->setOption('delete-list', []);
    }
    // In case of -y lets check user has provided all the required arguments.
    if (!$isInteractive && (!$package || !$scope)) {
      $messages[] = 'In order to use -y option, please provide a package and scope variable.';
    }
    // Get packages from user input.
    if ($isInteractive && empty($messages) && !$package) {
      $package = $this->getPackagesFromUserInput();
      $commandData->input()->setArgument('package', $package);
    }
    // Get scope from user input.
    if ($isInteractive && empty($messages) && !$scope) {
      $scope = $this->io()->choice(dt('Choose a scope.'), self::ALLOWED_SCOPE, NULL);
      $commandData->input()->setOption('scope', self::ALLOWED_SCOPE[$scope]);
    }
    if ($messages) {
      return new CommandError(implode(' ', $messages));
    }
  }

  /**
   * Validate the given delete list has valid configuration file.
   *
   * @param array $config_file
   *   The list of configurations file per scope.
   * @param array $delete_list_array
   *   The list of config file to be deleted.
   *
   * @return bool
   *   Boolean indicate true, false.
   */
  private function validDeleteList(array $config_file, array $delete_list_array) {
    $config_file_list = array_keys($config_file);
    $valid = TRUE;
    foreach ($delete_list_array as $config_name) {
      if (!in_array($config_name, $config_file_list)) {
        $valid = FALSE;
        break;
      }
    }
    return $valid;
  }

  /**
   * Check the provided packages are valid.
   *
   * @param array $packages
   *   The array of package.
   *
   * @return bool
   *   The status of package.
   */
  private function hasValidPackage(array $packages): bool {
    $valid_package = $this->getAcmsModules();
    foreach ($packages as $package) {
      if (!in_array($package, $valid_package)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Execute site studio rebuild after Acquia CMS config reset.
   *
   * @hook post-command acms:config-reset
   */
  public function postCommand($result, CommandData $commandData) {
    $scope = $commandData->input()->getOption('scope');
    if (in_array($scope, ['site-studio', 'all'])) {
      // Forcefully clear the cache after site is installed otherwise site
      // studio fails to rebuild.
      drupal_flush_all_caches();
      // Below code ensures that drush batch process doesn't hang. Unset all the
      // earlier created batches so that drush_backend_batch_process() can run
      // without being stuck.
      // @see https://github.com/drush-ops/drush/issues/3773 for the issue.
      $batch = &batch_get();
      $batch = NULL;
      unset($batch);
      $this->say(dt('Rebuilding all entities.'));
      $result = DX8CommandHelpers::rebuild([]);
      // Output results.
      $this->yell('Finished rebuilding.');
      // Status code.
      return is_array($result) && isset(array_shift($result)['error']) ? CommandResult::exitCode(self::EXIT_FAILURE) : CommandResult::exitCode(self::EXIT_SUCCESS);
    }
  }

}
