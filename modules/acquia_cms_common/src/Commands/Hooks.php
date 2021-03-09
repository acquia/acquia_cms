<?php

namespace Drupal\acquia_cms_common\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\cohesion\Drush\DX8CommandHelpers;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drush\Commands\DrushCommands;

/**
 * Implements Drush command hooks.
 */
final class Hooks extends DrushCommands {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a WebformSubmissionLogRouteSubscriber object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Alters the result of the config:get command.
   *
   * @hook alter config:get
   * @option $generic Automatically strip the UUID and site hash.
   * @usage config:get --generic system.site
   */
  public function processConfig($result, CommandData $command_data) {
    if ($command_data->input()->getOption('generic')) {
      unset($result['uuid'], $result['_core']);
    }
    return $result;
  }

  /**
   * Validate the result of the pm:enable command.
   *
   * For acquia_cms_starter and acquia_cms_demo_pubsec we implemented the
   * hook_requirements() to check if the other module is enabled or not.
   * Drush pm:enable command is not running hook_requirements().
   *
   * @todo Reference: https://github.com/drush-ops/drush/pull/4337/files
   * once patch will get applied to drush we can remove this method.
   *
   * @hook validate pm:enable
   */
  public function validateRequirement(CommandData $commandData) {
    $modules = $commandData->input()->getArgument('modules');
    // Run requirements checks on each module.
    // @see \drupal_check_module()
    require_once DRUSH_DRUPAL_CORE . '/includes/install.inc';
    foreach ($modules as $module) {
      module_load_install($module);
      $require_constants = [REQUIREMENT_ERROR, REQUIREMENT_WARNING];
      $requirements = $this->moduleHandler->invoke($module, 'requirements', ['install']);
      if (is_array($requirements) &&
        in_array(drupal_requirements_severity($requirements), $require_constants)) {
        $reasons = [];
        // Print any error messages.
        foreach ($requirements as $id => $requirement) {
          if (isset($requirement['severity']) && in_array($requirement['severity'], $require_constants)) {
            $message = $requirement['description'];
            if (isset($requirement['value']) && $requirement['value']) {
              $message = dt('@requirements_message (Currently using @item version @version)',
              [
                '@requirements_message' => $requirement['description'],
                '@item' => $requirement['title'],
                '@version' => $requirement['value'],
              ]);
            }
            $reasons[$id] = "$module: " . (string) $message;
          }
        }
        throw new \Exception(implode("\n", $reasons));
      }
    }
  }

  /**
   * Rebuild site studio when pubsec or starter is being enabled via drush.
   *
   * Cohesion_sync do not rebuild the site when pubsec is enabled via Drush.
   * Do a forceful rebuild whenever acquia_cms_demo_pubsec module is enabled.
   *
   * @hook post-command pm:enable
   */
  public function postCommand($result, CommandData $commandData) {
    $modules = $commandData->input()->getArgument('modules');
    if (in_array('acquia_cms_demo_pubsec', $modules)) {
      // Forcefully clear the cache after pubsec is enabled otherwise site
      // studio fails to rebuild.
      drupal_flush_all_caches();
      // Below code ensure that drush batch process doesn't hang. Unset all the
      // ealier created batches so that drush_backend_batch_process() can run
      // without being stuck.
      // @see https://github.com/drush-ops/drush/issues/3773 for the issue.
      $batch = &batch_get();
      $batch = NULL;
      unset($batch);
      $this->say(dt('Rebuilding all entities.'));
      $result = DX8CommandHelpers::rebuild([]);
      // Output results.
      $this->yell('Finished rebuilding.');
      // Status code.
      return is_array($result) && isset(array_shift($result)['error']) ? CommandResult::exitCode(self::EXIT_FAILURE) : CommandResult::exitCode(self::EXIT_SUCCESS);
    }
  }

}
