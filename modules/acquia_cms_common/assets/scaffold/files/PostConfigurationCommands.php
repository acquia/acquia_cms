<?php

namespace Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\acquia_cms_common\Event\PostConfigEvent;

/**
 * This acts on post configuration import/export event.
 */
class PostConfigurationCommands extends DrushCommands {

  /**
   * Dispatch event on config import.
   *
   * @hook post-command config-import
   */
  public function postImportProcess($result, CommandData $commandData): CommandResult {
    if (\Drupal::state()->get('acquia_cms.is_syncing')) {
      $this->processDispatchEvents('IMPORT');
    }

    return is_array($result) && isset(array_shift($result)['error']) ? CommandResult::exitCode(self::EXIT_FAILURE) : CommandResult::exitCode(self::EXIT_SUCCESS);
  }

  /**
   * Dispatch event on config export.
   *
   * @hook post-command config-export
   */
  public function postExportProcess($result, CommandData $commandData): CommandResult {
    if (\Drupal::state()->get('acquia_cms.is_syncing')) {
      $this->processDispatchEvents('EXPORT');
    }

    return is_array($result) && isset(array_shift($result)['error']) ? CommandResult::exitCode(self::EXIT_FAILURE) : CommandResult::exitCode(self::EXIT_SUCCESS);
  }

  /**
   * Process the dispatch of event as per configuration operation.
   *
   * @param string $type
   *   Value of configuration.
   */
  protected function processDispatchEvents(string $type): void {
    $eventObject = new PostConfigEvent();
    $eventName = PostConfigEvent::ACMS_POST_CONFIG_IMPORT;
    if ($type === 'EXPORT') {
      $eventName = PostConfigEvent::ACMS_POST_CONFIG_EXPORT;
    }
    $this->yell("Delegating post $type event!!");
    $eventDispatcher = \Drupal::service('event_dispatcher');
    // Dispatch event.
    $eventDispatcher->dispatch($eventObject, $eventName);
    // Delete after event dispatch.
    \Drupal::state()->delete('acquia_cms.is_syncing');
  }

}
