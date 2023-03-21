<?php

namespace Drupal\acquia_cms_config_management\Event;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Component\EventDispatcher\Event;
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
   * @var array|null
   */
  protected $result;

  /**
   * Holds the command data object.
   *
   * @var \Consolidation\AnnotatedCommand\CommandData|null
   */
  protected $commandData;

  /**
   * Constructs the object.
   *
   * @param array|null $result
   *   The result array or null.
   * @param \Consolidation\AnnotatedCommand\CommandData|null $commandData
   *   The command data object.
   */
  public function __construct(?array $result = [], ?CommandData $commandData = NULL) {
    $this->result = $result;
    $this->commandData = $commandData;
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
  public function getResult(): ?array {
    return $this->result;
  }

}
