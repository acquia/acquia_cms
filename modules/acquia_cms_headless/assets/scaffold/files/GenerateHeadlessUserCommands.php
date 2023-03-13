<?php

namespace Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;

/**
 * Programmatically create a new "headless" user on config import.
 */
class GenerateHeadlessUserCommands extends DrushCommands {

  /**
   * Create headless user on config import.
   *
   * @hook post-command config-import
   */
  public function postImportCreateHeadlessUser($result, CommandData $commandData): CommandResult {
    // Check headless is installed.
    if (\Drupal::state()->get('acquia_cms_headless.status') === 'enabled') {
      $starterKitNextjsService = \Drupal::getContainer()->get('acquia_cms_headless.starterkit_nextjs');
      // Programmatically create a new "headless" user.
      $this->yell("Creating headless user.");
      $starterKitNextjsService->createHeadlessUser();
      // Delete after user creation.
      \Drupal::state()->delete('acquia_cms_headless.status');

      return is_array($result) && isset(array_shift($result)['error']) ? CommandResult::exitCode(self::EXIT_FAILURE) : CommandResult::exitCode(self::EXIT_SUCCESS);
    }

    return is_array($result) && isset(array_shift($result)['error']) ? CommandResult::exitCode(self::EXIT_FAILURE) : CommandResult::exitCode(self::EXIT_SUCCESS);
  }

}
