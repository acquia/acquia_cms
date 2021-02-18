<?php

namespace Drupal\acquia_cms_common\Commands;

use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * A Drush command file.
 *
 * This files contains custom drush command to provide better support
 * and to increase maintainability for ACMS, we need tooling to quickly
 * diagnose and resolve config and database schema mismatch errors.
 */
class AcmsCommands extends DrushCommands implements SiteAliasManagerAwareInterface {
  use SiteAliasManagerAwareTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a ModuleHandlerInterface object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Get installed schema version of modules.
   *
   * A command to quickly get the installed schema versions of
   * one, some, or all active modules.
   *
   * @param array $options
   *   The options array.
   *
   * @option module
   *   The module whose schema version has to be display.
   * @command acms:get-schema
   * @option module  A comma-separated list of module name to get schema version.
   * @aliases ags
   * @usage acms:get-schema --module=acquia_cms_article,acquia_cms_common
   *   Display install schema version of given module.
   */
  public function getSchema(array $options = ['module' => NULL]) {
    if ($options['module']) {
      $modules = explode(',', $options['module']);
      foreach ($modules as $module_name) {
        if ($this->moduleHandler->moduleExists($module_name)) {
          $version = drupal_get_installed_schema_version($module_name);
          $this->output()->writeln("Currently installed schema version for '$module_name' is:$version");
        }
        else {
          $this->output()->writeln("Module: $module_name doesn't seems to be installed.");
        }
      }
    }
    // Lets get all modules and list the schema version currently installed.
    else {
      $modules = $this->moduleHandler->getModuleList();
      foreach ($modules as $module => $module_obj) {
        if ($module_obj->getType() === 'module') {
          $version = drupal_get_installed_schema_version($module);
          $this->output()->writeln("Currently installed schema version for '" . $module_obj->getName() . "' is: $version");
        }
      }
    }
  }

  /**
   * Command to perform update for pending database.
   *
   * @command acms:update-db
   * @aliases aupdb
   * @usage acms:update-db
   *   Perform update for pending database update.
   */
  public function updateDatabase() {
    // @todo Need to check that sumologic is adding the logs.
    $selfAlias = $this->siteAliasManager()->getSelf();
    $options = [
      'cache-clear' => TRUE,
      'entity-updates' => FALSE,
      'post-updates' => TRUE,
    ];
    $process = $this->processManager()->drush($selfAlias, 'updatedb', [], $options);
    $process->mustRun($process->showRealtime());
  }

  /**
   * Set schema version of particular module.
   *
   * Command to set the current schema version of particular
   * module to the previous value.
   *
   * @param string $module_name
   *   The name of module.
   * @param int $schema_version
   *   The module schema version.
   *
   * @command acms:rerun-schema
   * @aliases ars
   * @usage acms:rerun-schema
   *   Set the current schema version to the previous value.
   */
  public function setSchema(string $module_name, int $schema_version) {
    $min_required_version = 8000;
    // Lets check module exists and enabled, also schema version is correct.
    if ($this->moduleHandler->moduleExists($module_name)) {
      if ($schema_version >= $min_required_version) {
        $current_version = drupal_get_installed_schema_version($module_name);
        // Lets check we are setting only previous version of schema.
        if ($schema_version < $current_version) {
          drupal_set_installed_schema_version($module_name, $schema_version);
          $this->output()->writeln("Schema version set to $schema_version, now executing updatedb");
          $selfAlias = $this->siteAliasManager()->getSelf();
          $options = [
            'cache-clear' => TRUE,
            'entity-updates' => FALSE,
            'post-updates' => TRUE,
          ];
          $process = $this->processManager()->drush($selfAlias, 'updatedb', [], $options);
          $process->mustRun($process->showRealtime());
        }
        // No point in explicitly setting the current schema version.
        elseif ($schema_version === $current_version) {
          $this->output()->writeln("Currently '$module_name' has same schema version installed.");
        }
        // Warn user when trying to set next schema version
        // i.e $schema_version > $current_version.
        else {
          $this->output()->writeln("Invalid schema version for Module: $module_name");
        }
      }
      else {
        $this->output()->writeln("Invalid schema version for Module: $module_name");
      }
    }
    else {
      $this->output()->writeln("Module: $module_name doesn't seems to be installed.");
    }
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
        $question_string = 'Choose a Module to reset. Separate multiple choices with commas, e.g. "1,2,4".';
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
        // @todo Logic to import sets of configurations.
        foreach ($package as $module) {
          $this->importConfig($module, $options['scope']);
        }
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
      // @todo Logic to import sets of configurations.
      foreach ($package as $module) {
        $this->importConfig($module, $options['scope']);
      }
    }
  }

  /**
   * Import configurations for the given module & its scope.
   *
   * @param string $module
   *   The name of module.
   * @param string $scope
   *   Scope for configuration to import.
   */
  protected function importConfig(string $module, string $scope) {

    if ($scope == 'All') {
      // @todo Need to pass install, optional, schema along with dx8 folder.
      $source = drupal_get_path('module', $module) . '/config/install';
    }
    elseif ($scope == 'config') {
      // @todo Need to pass install, optional & schema folder.
      $source = drupal_get_path('module', $module) . '/config/optional';
    }
    elseif ($scope == 'site-studio') {
      // $source = drupal_get_path('module', $module) . '/config/dx8';
      // @todo Logic to check site studio key and import sets of configurations.
    }

    $selfAlias = $this->siteAliasManager()->getSelf();
    $options = ['partial' => TRUE];
    if (isset($source)) {
      $options['source'] = $source;
    }
    $process = $this->processManager()->drush($selfAlias, 'config:import', [], $options);
    $process->mustRun($process->showRealtime());
  }

}
