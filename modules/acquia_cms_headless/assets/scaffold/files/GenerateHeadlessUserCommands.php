<?php

namespace Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;

/**
 * Programmatically create a new "headless" user on config import.
 */
class GenerateHeadlessUserCommands extends DrushCommands {

  /**
   * Create headless user on config import.
   *
   * @hook post-command config-import
   */
  public function postImportCreateHeadlessUser($result, CommandData $commandData): void {
    $acmsUtilityService = \Drupal::getContainer()->get('acquia_cms_common.utility');
    $starterKitNextjsService = \Drupal::getContainer()->get('acquia_cms_headless.starterkit_nextjs');
    // Programmatically create a new "headless" user.
    if ($acmsUtilityService->getModulePreinstallTriggered() === 'acquia_cms_headless') {
      $starterKitNextjsService->createHeadlessUser();
    }
  }

}
