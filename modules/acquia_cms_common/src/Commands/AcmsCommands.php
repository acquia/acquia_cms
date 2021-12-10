<?php

namespace Drupal\acquia_cms_common\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandError;
use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
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
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * The System schema object from KeyValue factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactory
   */
  protected $systemSchema;

  /**
   * Constructs a ModuleHandlerInterface object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactory $key_value
   *   The System schema object from KeyValue factory.
   */
  public function __construct(ModuleHandlerInterface $module_handler, LoggerChannelFactoryInterface $loggerFactory, KeyValueFactory $key_value) {
    $this->moduleHandler = $module_handler;
    $this->loggerFactory = $loggerFactory->get('acquia_cms_db_update');
    $this->systemSchema = $key_value->get('system.schema');
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
   * @option modules
   *   The module whose schema version has to be display.
   * @command acms:get-schema
   * @option modules  A comma-separated list of modules
   * @aliases ags
   * @usage acms:get-schema --modules=acquia_cms_article,acquia_cms_common
   *   Display installed schema version of given module(s).
   */
  public function getSchema(array $options = ['modules' => NULL]) {
    $modules = [];
    if (empty($options['modules'])) {
      $modules = array_keys($this->moduleHandler->getModuleList());
    }
    else {
      foreach ($options['modules'] as $module) {
        if ($this->moduleHandler->moduleExists($module)) {
          $modules[] = $module;
        }
        else {
          $rows[] = [$module, dt("Module is not installed.")];
        }
      }
    }
    foreach ($modules as $module) {
      $version = $this->systemSchema->get($module);
      $rows[] = [$module, $version];
    }

    // Show result in table format.
    if (!empty($rows)) {
      $this->io()->table(['Module name', 'Installed schema version'], $rows);
    }
  }

  /**
   * Hook validate for acms:get-schema command.
   *
   * @hook validate acms:get-schema
   */
  public function validateGetSchemaCommand(CommandData $commandData) {
    $modules = $commandData->input()->getOption('modules');
    $messages = [];
    if ($modules) {
      $modules_array = array_filter(explode(',', $modules));
      if (empty($modules_array)) {
        $messages[] = dt("Invalid value for module options");
      }
      else {
        $commandData->input()->setOption('modules', $modules_array);
      }
    }
    if ($messages) {
      return new CommandError(implode(' ', $messages));
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
    $selfAlias = $this->siteAliasManager()->getSelf();
    $options = [
      'cache-clear' => TRUE,
      'entity-updates' => FALSE,
      'post-updates' => TRUE,
    ];
    $process = $this->processManager()->drush($selfAlias, 'updatedb', [], $options);
    $process->mustRun();

    // Use symfony process component getIterator to get all output
    // and log them in system using drupal logger service
    // so that sumo logic can take the logs from system.
    // https://symfony.com/doc/current/components/process.html#usage
    $iterator = $process->getIterator($process::ITER_SKIP_OUT);
    foreach ($iterator as $message) {
      $this->logMessage($message);
    }
  }

  /**
   * Helper to log message using logger.
   *
   * @param string $message
   *   The message string.
   */
  private function logMessage(string $message) {
    $message_array = array_filter(explode(PHP_EOL, $message));
    foreach ($message_array as $log) {
      $str_text = str_replace('> ', '', $log);
      $this->loggerFactory->notice($str_text);
    }
  }

  /**
   * Set schema version of particular module.
   *
   * A command to re-run a specific hook_update_n() command.
   *
   * @param string $module_name
   *   The name of module.
   * @param int $schema_version
   *   The module schema version.
   *
   * @command acms:rerun-schema
   * @aliases ars
   * @usage acms:rerun-schema acquia_cms_article 8000
   *   Set the current schema version to the given value.
   */
  public function setSchema(string $module_name, int $schema_version) {
    $this->systemSchema->set($module_name, $schema_version);
    $this->output()->writeln(dt("Schema version set to $schema_version. now executing updatedb"));
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
   * Hook validate for acms:rerun-schema command.
   *
   * @hook validate acms:rerun-schema
   */
  public function validateSetSchemaCommand(CommandData $commandData) {
    $min_required_version = 8000;
    $args = $commandData->input()->getArguments();
    $messages = [];
    if (!$this->moduleHandler->moduleExists($args['module_name'])) {
      $messages[] = dt("Module [@module_name] is not installed.", ['@module_name' => $args['module_name']]);
    }
    if ($this->moduleHandler->moduleExists($args['module_name'])) {
      $current_version = $this->systemSchema->get($args['module_name']);
      // Do not allow schema version smaller than minimum required version
      // & bigger that currently installed version.
      if ($args['schema_version'] < $min_required_version || $args['schema_version'] > $current_version) {
        $messages[] = dt("Invalid schema version for module [@module_name]", ['@module_name' => $args['module_name']]);
      }
      // No point in explicitly setting the current schema version.
      if ($args['schema_version'] == $current_version) {
        $messages[] = dt("Currently module [@module_name] has same schema version installed.", ['@module_name' => $args['module_name']]);
      }
    }
    if ($messages) {
      return new CommandError(implode(' ', $messages));
    }
  }

}
