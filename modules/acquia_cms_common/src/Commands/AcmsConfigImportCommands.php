<?php

namespace Drupal\acquia_cms_common\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandError;
use Drupal\acquia_cms\Facade\CohesionFacade;
use Drupal\acquia_cms_common\Services\AcmsUtilityService;
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
  protected $classResolver;

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
    $this->classResolver = $classResolver;
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
    $this->io()->text(["Welcome to the Acquia CMS config reset wizard.",
      "This should be used with extreme caution and can lead to unexpected behavior on your site if not well tested.",
      "Do not run this in production until you've tested it in a safe, non-public environment first.",
    ]);
    // Lets get input from user if not provided package with command.
    if (empty($package)) {
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
   * Get lists of module only.
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
   * @throws \Drush\Exceptions\UserAbortException
   * @throws \Exception
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

    if (!$this->io()->confirm(dt('Import these configuration changes?'))) {
      throw new UserAbortException();
    }
    $this->configImportCommands->doImport($storage_comparer);
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
      $messages[] = 'Given packages are not valid, try providing a list of ACMS modules ex: acquia_cms_article';
    }
    // In case of -y lets check user has provided all the required arguments.
    if (!$isInteractive && (!$package || !$scope)) {
      $messages[] = 'In order to use -y option, please provide a package and scope variable.';
    }
    if ($messages) {
      return new CommandError(implode(' ', $messages));
    }
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

}
