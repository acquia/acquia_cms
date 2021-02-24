<?php

namespace Drupal\acquia_cms_common\Commands;

use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drush\Commands\DrushCommands;

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
   * @usage acms:get-schema --modules=acquia_cms_article,acquia_cms_common
   *   Display install schema version of given module.
   */
  public function getSchema(array $options = ['modules' => NULL]) {
    $rows = [];
    if ($options['modules']) {
      $modules = explode(',', $options['modules']);
      foreach ($modules as $module_name) {
        if (!$this->moduleHandler->moduleExists($module_name)) {
          $this->io()->error("Module: $module_name doesn't seems to be installed.");
          break;
        }
        $version = drupal_get_installed_schema_version($module_name);
        $rows[] = [$module_name, $version];
      }
    }
    // Get all modules, themes & profile and list
    // the currently installed schema version.
    else {
      $modules = $this->moduleHandler->getModuleList();
      foreach ($modules as $module => $module_obj) {
        $version = drupal_get_installed_schema_version($module);
        $rows[] = [$module, $version];
      }
    }
    // Show result in table format.
    if (!empty($rows)) {
      $this->io()->table(['Module name', 'Installed schema version'], $rows);
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
    if (!$this->moduleHandler->moduleExists($module_name)) {
      $this->io()->error("Module: $module_name doesn't seems to be installed.");
      return;
    }
    if ($schema_version >= $min_required_version) {
      $current_version = drupal_get_installed_schema_version($module_name);
      // Lets check we are setting only previous version of schema.
      if ($schema_version < $current_version) {
        drupal_set_installed_schema_version($module_name, $schema_version);
        $this->output()->writeln(dt("Schema version set to $schema_version, now executing updatedb"));
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
        $this->io()->note("Currently '$module_name' has same schema version installed.");
      }
      // Warn user when trying to set next schema version
      // i.e $schema_version > $current_version.
      else {
        $this->io()->error("Invalid schema version for Module: $module_name");
      }
    }
    else {
      $this->io()->error("Invalid schema version for Module: $module_name");
    }
  }

}
