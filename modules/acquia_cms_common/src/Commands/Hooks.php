<?php

namespace Drupal\acquia_cms_common\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\acquia_cms_common\Services\AcmsUtilityService;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drush\Commands\DrushCommands;
use Drush\Drush;

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
   * The The acms utility service.
   *
   * @var \Drupal\acquia_cms_common\Services\AcmsUtilityService
   */
  protected $acmsUtilityService;

  /**
   * Constructs a WebformSubmissionLogRouteSubscriber object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\acquia_cms_common\Services\AcmsUtilityService $acms_utility_service
   *   The acms utility service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, AcmsUtilityService $acms_utility_service) {
    $this->moduleHandler = $module_handler;
    $this->acmsUtilityService = $acms_utility_service;
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
      $requirements = $this->moduleHandler->invoke($module, 'requirements', ['install']);
      if (is_array($requirements) && drupal_requirements_severity($requirements) == REQUIREMENT_ERROR) {
        $reasons = [];
        // Print any error messages.
        foreach ($requirements as $id => $requirement) {
          if (isset($requirement['severity']) && $requirement['severity'] == REQUIREMENT_ERROR) {
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
    if (is_array($requirements) && drupal_requirements_severity($requirements) == REQUIREMENT_WARNING) {
      foreach ($requirements as $id => $requirement) {
        Drush::logger()->warning(dt($requirement['description']));
      }
    }
  }

  /**
   * Run site studio rebuild after acquia_cms_site_studio module enable.
   *
   * @hook post-command pm:enable
   */
  public function postCommand($result, CommandData $commandData) {
    if (in_array('acquia_cms_site_studio', $commandData->getArgsWithoutAppName()['modules'])) {
      $this->say(dt('Rebuilding all entities.'));
      $result = $this->acmsUtilityService->rebuildSiteStudio();
      $this->yell('Finished rebuilding.');
      return is_array($result) && isset(array_shift($result)['error']) ? CommandResult::exitCode(self::EXIT_FAILURE) : CommandResult::exitCode(self::EXIT_SUCCESS);
    }
  }

}
