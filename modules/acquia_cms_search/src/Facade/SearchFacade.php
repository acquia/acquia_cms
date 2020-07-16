<?php

namespace Drupal\acquia_cms_search\Facade;

use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\search_api\SearchApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a facade for integrating with Search API.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class SearchFacade implements ContainerInjectionInterface {

  /**
   * The config installer service.
   *
   * @var \Drupal\Core\Config\ConfigInstallerInterface
   */
  private $configInstaller;

  /**
   * The search index entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $indexStorage;

  /**
   * The view entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $viewStorage;

  /**
   * SearchFacade constructor.
   *
   * @param \Drupal\Core\Config\ConfigInstallerInterface $config_installer
   *   The config installer service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $index_storage
   *   The search index entity storage handler.
   * @param \Drupal\Core\Entity\EntityStorageInterface $view_storage
   *   The view entity storage handler.
   */
  public function __construct(ConfigInstallerInterface $config_installer, EntityStorageInterface $index_storage, EntityStorageInterface $view_storage) {
    $this->configInstaller = $config_installer;
    $this->indexStorage = $index_storage;
    $this->viewStorage = $view_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');

    return new static(
      $container->get('config.installer'),
      $entity_type_manager->getStorage('search_api_index'),
      $entity_type_manager->getStorage('view')
    );
  }

  /**
   * Acts on a newly created node type.
   *
   * Tries to add the node type to the list of indexed bundles handled by the
   * index specified by 'acquia_cms.search_index' third-party setting.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The new node type.
   */
  public function addNodeType(NodeTypeInterface $node_type) {
    // We don't want to do any secondary config writes during a config sync,
    // since that can have major, unintentional side effects.
    if ($this->configInstaller->isSyncing()) {
      return;
    }

    $index = $node_type->getThirdPartySetting('acquia_cms', 'search_index');
    if (empty($index)) {
      return;
    }

    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->indexStorage->load($index);
    if (empty($index)) {
      return;
    }

    try {
      $data_source = $index->getDatasource('entity:node');
    }
    catch (SearchApiException $e) {
      // If the index isn't handling nodes at all, we're in the Twilight Zone
      // and there's nothing else for us to do.
      return;
    }
    $node_type_id = $node_type->id();

    // Add this node type to the data source.
    $configuration = $data_source->getConfiguration();
    $configuration['bundles']['selected'][] = $node_type_id;
    $data_source->setConfiguration($configuration);

    // Adding view mode in renderer HTML field.
    $field = $index->getField('rendered_item');
    if ($field) {
      $configuration = $field->getConfiguration();
      $configuration['view_mode']['entity:node'][$node_type_id] = 'search_index';
      $field->setConfiguration($configuration);
    }
    $this->indexStorage->save($index);

    // Update the view mode used for this node type in the Search view.
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = $this->viewStorage->load('search');
    if (empty($view)) {
      return;
    }

    $display = &$view->getDisplay('default');
    if ($display['display_options']['row']['type'] == 'search_api') {
      $display['display_options']['row']['options']['view_modes']['entity:node'][$node_type_id] = 'teaser';
      $this->viewStorage->save($view);
    }
  }

}
