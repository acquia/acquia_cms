<?php

namespace Drupal\acquia_cms_common;

use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * Constructs a new AcmsModulesUninstallValidator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module): array {
    $reasons = [];
    $type = explode('_', $module)[2];
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
      $reasons[] = $this->t('There is content available for node type [@type], please manually delete content before uninstallation.', [
        '@type' => $type,
      ]);
    }
    elseif (in_array($module, $allowed_media_modules)  && $this->hasMedia($type)) {
      $reasons[] = $this->t('There is media available for type [@type], please manually delete content before uninstallation.', [
        '@type' => $type,
      ]);
    }
    return $reasons;
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

}
