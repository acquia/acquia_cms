<?php

namespace Drupal\acquia_cms_common;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Module uninstall validator for acquia cms modules.
 */
class AcmsModulesUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory service object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new AcmsModulesUninstallValidator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    TranslationInterface $string_translation,
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module): array {
    $reasons = [];
    $type = explode('_', $module)[2] ?? NULL;
    $allowed_modules = [
      'acquia_cms_article',
      'acquia_cms_event',
      'acquia_cms_page',
      'acquia_cms_person',
      'acquia_cms_place',
    ];
    $allowed_media_modules = [
      'acquia_cms_document',
      'acquia_cms_image',
      'acquia_cms_video',
    ];
    if (in_array($module, $allowed_modules) && $this->hasContent($type)) {
      $reasons[] = $this->t('There are content available for node type [@type], please manually delete content before uninstallation.', [
        '@type' => $type,
      ]);
    }
    elseif (in_array($module, $allowed_media_modules)  && $this->hasMedia($type)) {
      $reasons[] = $this->t('There are media available for type [@type], please manually delete content before uninstallation.', [
        '@type' => $type,
      ]);
    }
    elseif ($module == 'acquia_cms_starter'  && $type = $this->hasMediaAndContent()) {
      $reasons[] = $this->t('There are content/media available for type [@type], please manually delete content before uninstallation.', [
        '@type' => $type,
      ]);
    }
    $sitestudio_modules = [
      'acquia_cms_article',
      'acquia_cms_event',
      'acquia_cms_image',
      'acquia_cms_page',
      'acquia_cms_person',
      'acquia_cms_place',
      'acquia_cms_search',
      'acquia_cms_site_studio',
      'acquia_cms_video',
    ];
    if ($this->moduleHandler->moduleExists('acquia_cms_site_studio') &&
    in_array($module, $sitestudio_modules) &&
    $this->hasSiteStudioPackage($module)) {
      $reasons[] = $this->t('There are site studio package available for module [@module], please manually delete package before uninstallation.', [
        '@module' => $module,
      ]);
    }
    return $reasons;
  }

  /**
   * Get entity type id if content/media available.
   */
  private function hasMediaAndContent() {
    $node_types = ['article', 'page', 'event', 'person', 'place'];
    $media_types = ['document', 'image', 'video'];
    foreach ($node_types as $type) {
      $has_reason = $this->hasContent($type);
      if ($has_reason) {
        $has_reason = $type;
        break;
      }
    }
    if ($has_reason) {
      return $has_reason;
    }
    foreach ($media_types as $type) {
      $has_reason = $this->hasMedia($type);
      if ($has_reason) {
        $has_reason = $type;
        break;
      }
    }
    return $has_reason;
  }

  /**
   * Check if node with certain type has content available.
   *
   * @param string $node_type
   *   The node type.
   *
   * @return bool
   *   The status of data available for certain node type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function hasContent(string $node_type): bool {
    if ($node_type) {
      $nodes = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('type', $node_type)
        ->execute();
      return (bool) $nodes;
    }
    return FALSE;
  }

  /**
   * Check if media with certain type is available.
   *
   * @param string $media_type
   *   The media type.
   *
   * @return bool
   *   The status of data available for certain node type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function hasMedia(string $media_type): bool {
    if ($media_type) {
      $media = $this->entityTypeManager->getStorage('media')->getQuery()
        ->condition('bundle', $media_type)
        ->execute();
      return (bool) $media;
    }
    return FALSE;
  }

  /**
   * Check if media with certain type is available.
   *
   * @param string $module
   *   The media type.
   *
   * @return bool
   *   The status of data available for certain node type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function hasSiteStudioPackage(string $module): bool {
    if ($module) {
      $package = $config = [];
      switch ($module) {
        case 'acquia_cms_article':
          $package = 'pack_acquia_cms_article';
          break;

        case 'acquia_cms_event':
          $package = 'pack_acquia_cms_event';
          break;

        case 'acquia_cms_image':
          $package = 'pack_acquia_cms_image';
          break;

        case 'acquia_cms_page':
          $package = 'pack_acquia_cms_page';
          break;

        case 'acquia_cms_person':
          $package = 'pack_acquia_cms_person';
          break;

        case 'acquia_cms_place':
          $package = 'pack_acquia_cms_place';
          break;

        case 'acquia_cms_search':
          $package = 'pack_acquia_cms_search';
          break;

        case 'acquia_cms_site_studio':
          $package = 'pack_acquia_cms_core';
          break;

        case 'acquia_cms_video':
          $package = 'pack_acquia_cms_video';
          break;
      }
      $config = $this->configFactory->getEditable('cohesion_sync.cohesion_sync_package.' . $package)->get('id');
      return (bool) $config;
    }
    return FALSE;
  }

}
