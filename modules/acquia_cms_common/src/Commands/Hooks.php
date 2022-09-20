<?php

namespace Drupal\acquia_cms_common\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\acquia_cms_common\Services\AcmsUtilityService;
use Drupal\Core\Config\ConfigFactoryInterface;
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
   * The acms utility service.
   *
   * @var \Drupal\acquia_cms_common\Services\AcmsUtilityService
   */
  protected $acmsUtilityService;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a WebformSubmissionLogRouteSubscriber object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\acquia_cms_common\Services\AcmsUtilityService $acms_utility_service
   *   The acms utility service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ModuleHandlerInterface $module_handler, AcmsUtilityService $acms_utility_service, ConfigFactoryInterface $config_factory) {
    $this->moduleHandler = $module_handler;
    $this->acmsUtilityService = $acms_utility_service;
    $this->configFactory = $config_factory;
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

}
