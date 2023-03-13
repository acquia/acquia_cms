<?php

namespace Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\acquia_cms_common\Services\AcmsUtilityService;
use Drupal\acquia_cms_headless\Service\StarterkitNextjsService;

/**
 * Execute site studio package export/import on config export/import.
 */
class GenerateHeadlessUser extends DrushCommands {

  /**
   * The acquia cms utility service.
   *
   * @var \Drupal\acquia_cms_common\Services\AcmsUtilityService
   */
  protected $acmsUtilityService;

  /**
   * Provides Starter Kit Next.js Service.
   *
   * @var \Drupal\acquia_cms_headless\Service\StarterkitNextjsService
   */
  protected $starterKitNextjsService;

  /**
   * Constructs a GenerateHeadlessUser object.
   *
   * @param \Drupal\acquia_cms_common\Services\AcmsUtilityService $acms_utility_service
   *   The acms utility service.
   * @param \Drupal\acquia_cms_headless\Service\StarterkitNextjsService $starterkit_nextjs_service
   *   The starterkit nextjs service.
   */
  public function __construct(AcmsUtilityService $acms_utility_service, StarterkitNextjsService $starterkit_nextjs_service) {
    $this->acmsUtilityService = $acms_utility_service;
    $this->starterKitNextjsService = $starterkit_nextjs_service;
  }

  /**
   * Create headless user on config import.
   *
   * @hook post-command config-import
   */
  public function postImportCreateHeadlessUser($result, CommandData $commandData): void {
    if ($this->acmsUtilityService->getModulePreinstallTriggered() === 'acquia_cms_headless') {
      // Programmatically create a new "headless" user.
      $this->starterKitNextjsService->createHeadlessUser();
    }

  }

}
