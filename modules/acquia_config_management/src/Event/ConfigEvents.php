<?php

namespace Drupal\acquia_config_management\Event;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Component\EventDispatcher\Event;
use Drush\Commands\acquia_global_commands\ConfigImportExportCommands;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Event that is fired after configurations are imported & exported.
 */
class ConfigEvents extends Event {

  /**
   * The post configuration export event.
   */
  const POST_CONFIG_EXPORT = 'post_config_export_event';

  /**
   * The post configuration import event.
   */
  const POST_CONFIG_IMPORT = 'post_config_import_event';

  /**
   * The site install with existing configuration event.
   */
  const POST_SITE_INSTALL_EXISTING_CONFIG = 'post_site_install_existing_config';

  /**
   * Holds the command result data.
   *
   * @var \Consolidation\AnnotatedCommand\CommandResult|null
   */
  protected $result;

  /**
   * Holds the command data object.
   *
   * @var \Consolidation\AnnotatedCommand\CommandData|null
   */
  protected $commandData;

  /**
   * Holds the object of ConfigImportExportCommands.
   *
   * @var \Drush\Commands\acquia_global_commands\ConfigImportExportCommands|null
   */
  public $acquiaGlobalCommand;

  /**
   * Constructs the object.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData|null $result
   *   The result array or null.
   * @param \Consolidation\AnnotatedCommand\CommandData|null $commandData
   *   The command data object.
   * @param \Drush\Commands\acquia_global_commands\ConfigImportExportCommands|null $acquiaGlobalCommand
   *   The config import export command.
   */
  public function __construct(
    ?CommandResult $result,
    ?CommandData $commandData = NULL,
    ?ConfigImportExportCommands $acquiaGlobalCommand = NULL) {
    $this->result = $result;
    $this->commandData = $commandData;
    $this->acquiaGlobalCommand = $acquiaGlobalCommand;
  }

  /**
   * Returns the symfony input object.
   */
  public function getInput(): ?InputInterface {
    return $this->commandData instanceof CommandData ? $this->commandData->input() : NULL;
  }

  /**
   * Returns the symfony output object.
   */
  public function getOutput(): ?OutputInterface {
    return $this->commandData instanceof CommandData ? $this->commandData->output() : NULL;
  }

  /**
   * Returns the command result.
   */
  public function getResult(): ?CommandResult {
    return $this->result;
  }

}
