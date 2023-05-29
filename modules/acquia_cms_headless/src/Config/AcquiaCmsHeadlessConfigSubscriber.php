<?php

namespace Drupal\acquia_cms_headless\Config;

use Drupal\acquia_cms_headless\Service\StarterkitNextjsService;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to configuration events for the Default Content module.
 */
class AcquiaCmsHeadlessConfigSubscriber implements EventSubscriberInterface {

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
   */
  public static function getSubscribedEvents(): array {
    return [ConfigEvents::IMPORT => 'onConfigImport'];
  }

  /**
   * Creates headless user after config synchronization.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The config importer event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onConfigImport(ConfigImporterEvent $event) {
    $enabled_extensions = $event->getConfigImporter()->getExtensionChangelist('module', 'install');
    // Create headless user.
    if (in_array('acquia_cms_headless', $enabled_extensions)) {
      $this->starterKitService->createHeadlessUser();
    }
  }

}
