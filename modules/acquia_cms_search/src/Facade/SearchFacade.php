<?php

namespace Drupal\acquia_cms_search\Facade;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a facade for integrating with Seach API.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class SearchFacade implements ContainerInjectionInterface {

  /**
   * The entityTypeManger service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * SearchFacade constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Acts on a newly created node type.
   *
   * Tries to add the node_type in the list of indexed bundle, as
   * specified by the 'acquia_cms.search_index' third-party setting.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The new node type.
   */
  public function addNodeType(NodeTypeInterface $node_type) {
    $search_index = $node_type->getThirdPartySetting('acquia_cms', 'search_index', []);

    if (!empty($search_index)) {
      $index_storage = $this->entityTypeManager->getStorage('search_api_index');
      $index = $index_storage->load($search_index);
      if (is_object($index)) {
        // Updating bundles in the datasource.
        $data_source = $index->getDatasource('entity:node');
        if ($data_source) {
          $configuration = $data_source->getConfiguration();
          $configuration['bundles']['selected'][] = $node_type->id();
          $data_source->setConfiguration($configuration);
        }
      }
      // Adding view mode in renderer HTML field.
      $field = $index->getField('rendered_item');
      if ($field) {
        $configuration = $field->getConfiguration();
        $configuration['view_mode']['entity:node'][$node_type->id()] = 'search_index';
        $field->setConfiguration($configuration);
      }
      $index_storage->save($index);
      // Updating view modes in search view.
      $view_storage = $this->entityTypeManager->getStorage('view');
      $view = $view_storage->load('search');
      if (!empty($view)) {
        $display = &$view->getDisplay('default');
        if ($display['display_options']['row']['type'] == 'search_api') {
          $display['display_options']['row']['options']['view_modes']['entity:node'][$node_type->id()] = 'teaser';
          $view_storage->save($view);
        }
      }
    }
  }

}
