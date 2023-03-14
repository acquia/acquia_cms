<?php

namespace Drupal\acquia_cms_headless\EventSubscriber;

use Drupal\acquia_cms_common\Event\PostConfigEvent;
use Drupal\acquia_cms_headless\Service\StarterkitNextjsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Process headless related manipulation on post configuration import/export.
 *
 * @package Drupal\acquia_cms_headless\EventSubscriber
 */
class HeadlessPostConfigEventsSubscriber implements EventSubscriberInterface {

  /**
   * Starter-kit service.
   *
   * @var \Drupal\acquia_cms_headless\Service\StarterkitNextjsService
   */
  protected $starterKitService;

  /**
   * Constructs a new HeadlessPostConfigEventsSubscriber object.
   *
   * @param \Drupal\acquia_cms_headless\Service\StarterkitNextjsService $acms_starter_kit_service
   *   The acms utility service.
   */
  public function __construct(StarterkitNextjsService $acms_starter_kit_service) {
    $this->starterKitService = $acms_starter_kit_service;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      PostConfigEvent::ACMS_POST_CONFIG_IMPORT => 'onPostConfigImport',
    ];
  }

  /**
   * Post config import manipulation.
   */
  public function onPostConfigImport() {
    // Create headless user.
    $this->starterKitService->createHeadlessUser();
  }

}
