<?php

namespace Drupal\sitestudio_config_management\Traits;

use Drush\Drush;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Trait to execute Drush commands.
 */
trait DrushCommandTrait {

  /**
   * Determines if trait is initialized.
   *
   * @var bool
   */
  protected $initialized = FALSE;

  /**
   * The drush path alias service object.
   *
   * @var \Consolidation\SiteAlias\SiteAliasManager
   */
  protected $siteAliasManager;

  /**
   * The symfony process manager service.
   *
   * @var \Drush\SiteAlias\ProcessManager
   */
  protected $processManager;

  /**
   * An array of commands to execute.
   *
   * @var array
   */
  protected $commands = [];

  /**
   * Adds the command in array to execute.
   */
  public function addCommand(string $command): void {
    $this->commands[] = $command;
  }

  /**
   * Initialize the trait.
   */
  protected function initialize(): void {
    $this->siteAliasManager = Drush::aliasManager();
    $this->processManager = Drush::processManager();
    $this->initialized = TRUE;
  }

  /**
   * Runs the drush command & return exit code.
   *
   * @param string $command
   *   The drush command to execute.
   */
  protected function runDrushCommand(string $command): bool {
    $isSuccess = TRUE;
    $process = $this->processManager->drush($this->siteAliasManager->getSelf(), $command);
    try {
      $this->logger()->notice("Running command: " . sprintf('> %s', $process->getCommandLine()));
      $process->mustRun($process->showRealtime());
    }
    catch (ProcessFailedException $e) {
      $this->logger()->error($e->getMessage());
      $isSuccess = FALSE;
    }
    return $isSuccess;
  }

  /**
   * Executes each command one by one and returns TRUE if success or FALSE.
   */
  public function execute(): bool {
    if (!$this->initialized) {
      $this->initialize();
    }
    $commandRanSuccessfully = TRUE;
    foreach ($this->commands as $key => $command) {
      if (!$this->runDrushCommand($command)) {
        $commandRanSuccessfully = FALSE;
        break;
      }
      else {
        unset($this->commands[$key]);
      }
    }
    return $commandRanSuccessfully;
  }

  /**
   * Returns the Drupal or Drush logger object.
   */
  abstract protected function logger(): ?LoggerInterface;

}
