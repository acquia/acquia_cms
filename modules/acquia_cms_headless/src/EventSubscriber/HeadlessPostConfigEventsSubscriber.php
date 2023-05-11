<?php

namespace Drupal\acquia_cms_headless\EventSubscriber;

use Drupal\acquia_cms_headless\Service\StarterkitNextjsService;
use Drupal\acquia_config_management\Event\ConfigEvents;
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
    $events = [];
    if (class_exists(ConfigEvents::class)) {
      $events[ConfigEvents::POST_SITE_INSTALL_EXISTING_CONFIG] = 'createHeadlessUser';
    }

    return $events;
  }

  /**
   * Post config import manipulation.
   */
  public function createHeadlessUser($event) {
    // Create headless user.
    $this->starterKitService->createHeadlessUser();
  }

}
