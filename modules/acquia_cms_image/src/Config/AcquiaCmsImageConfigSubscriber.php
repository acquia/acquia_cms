<?php

namespace Drupal\acquia_cms_image\Config;

use Drupal\acquia_cms_image\SiteLogo;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to configuration events for the Default Content module.
 */
class AcquiaCmsImageConfigSubscriber implements EventSubscriberInterface {

  /**
   * The SiteLogo class object.
   *
   * @var \Drupal\acquia_cms_image\SiteLogo
   */
  protected $siteLogo;

  /**
   * {@inheritdoc}
   */
  public function __construct(ClassResolverInterface $classResolver) {
    $this->siteLogo = $classResolver->getInstanceFromDefinition(SiteLogo::class);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [ConfigEvents::IMPORT => 'onConfigImport'];
  }

  /**
   * Creates default content after config synchronization.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The config importer event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onConfigImport(ConfigImporterEvent $event) {
    $enabled_extensions = $event->getConfigImporter()->getExtensionChangelist('module', 'install');
    $themeConfig = $event->getConfigImporter()->getStorageComparer()->getSourceStorage()->read("system.theme.global");
    $logoPath = $themeConfig['logo']['path'] ?? '';

    // We are creating media content for logo, if module is getting installed
    // using existing configurations and if the user has not changed the logo
    // provided by the acquia_cms_image module.
    if (in_array('acquia_cms_image', $enabled_extensions) && $logoPath == SiteLogo::LOGO_PATH) {
      $this->siteLogo->createLogo();
    }
  }

}
