<?php

namespace Drupal\acquia_cms_search\Facade;

use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Utility\FieldsHelperInterface;
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
   * The Search API fields helper service.
   *
   * @var \Drupal\search_api\Utility\FieldsHelperInterface
   */
  private $fieldsHelper;

  /**
   * The field config storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $fieldConfigStorage;

  /**
   * SearchFacade constructor.
   *
   * @param \Drupal\Core\Config\ConfigInstallerInterface $config_installer
   *   The config installer service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $index_storage
   *   The search index entity storage handler.
   * @param \Drupal\Core\Entity\EntityStorageInterface $view_storage
   *   The view entity storage handler.
   * @param \Drupal\Core\Entity\EntityStorageInterface $field_config_storage
   *   The view filed config storage handler.
   * @param \Drupal\search_api\Utility\FieldsHelperInterface $fields_helper
   *   The Search API fields helper service.
   */
  public function __construct(ConfigInstallerInterface $config_installer, EntityStorageInterface $index_storage, EntityStorageInterface $view_storage, EntityStorageInterface $field_config_storage, FieldsHelperInterface $fields_helper) {
    $this->configInstaller = $config_installer;
    $this->indexStorage = $index_storage;
    $this->viewStorage = $view_storage;
    $this->fieldsHelper = $fields_helper;
    $this->fieldConfigStorage = $field_config_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');

    return new static(
      $container->get('config.installer'),
      $entity_type_manager->getStorage('search_api_index'),
      $entity_type_manager->getStorage('view'),
      $entity_type_manager->getStorage('field_config'),
      $container->get('search_api.fields_helper')
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
    $index = $this->loadIndexFromSettings($node_type);
    if (empty($index)) {
      return;
    }

    $node_type_id = $node_type->id();

    // Add this node type to the data source.
    $data_source = $index->getDatasource('entity:node');
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

    // @todo re-visit to see if below code can be improve further
    // currently its getting called for each node types.
    // Retroactively enable indexing for any fields that existed before this
    // module was installed.
    $fields = $this->fieldConfigStorage->loadMultiple();
    array_walk($fields, 'acquia_cms_search_field_config_insert');

    // Update the view mode used for this node type in the Search view.
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = $this->viewStorage->load('search');
    if (empty($view)) {
      return;
    }

    // Update node view mode with teaser for newly created content types
    // where display options of row type usage search_api.
    $display = &$view->getDisplay('default');
    if ($display['display_options']['row']['type'] == 'search_api') {
      $node_type_view_mode = $display['display_options']['row']['options']['view_modes']['entity:node'][$node_type_id];
      if ($node_type_view_mode !== 'teaser') {
        $display['display_options']['row']['options']['view_modes']['entity:node'][$node_type_id] = 'teaser';
        $this->viewStorage->save($view);
      }
    }
  }

  /**
   * Acts on a newly created taxonomy term reference field.
   *
   * Tries to add the field to the list of fields known to the index specified
   * by 'acquia_cms.search_index' third-party setting.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $field_storage
   *   The new field's storage definition.
   */
  public function addTaxonomyField(FieldStorageConfigInterface $field_storage) {
    $index = $this->loadIndexFromSettings($field_storage);
    if (empty($index)) {
      return;
    }

    $field_name = $field_storage->getName();

    // Bail out if the field already exists on the index.
    if ($index->getField($field_name)) {
      return;
    }

    // Field storages don't normally have a human-readable label, so allow it to
    // provide one in its third-party settings.
    $field_label = $field_storage->getThirdPartySetting('acquia_cms_common', 'search_label') ?: $field_storage->getLabel();

    $data_source_id = 'entity:' . $field_storage->getTargetEntityTypeId();
    // This will throw an exception if the data source doesn't exist, so this
    // is really just a way to prevent the field from using an invalid data
    // source.
    $data_source_id = $index->getDatasource($data_source_id)->getPluginId();

    // Add the referenced term's ID to the index.
    $field = $this->fieldsHelper->createField($index, $field_name)
      ->setLabel($field_label)
      ->setDatasourceId($data_source_id)
      ->setPropertyPath($field_name)
      ->setType('integer');
    $index->addField($field);

    // Add the referenced term's label to the index.
    $field = $this->fieldsHelper->createField($index, $field_name . '_name')
      ->setLabel("$field_label: Name")
      ->setDatasourceId($data_source_id)
      ->setPropertyPath("$field_name:entity:name")
      ->setType('string');
    $index->addField($field);

    $this->indexStorage->save($index);
  }

  /**
   * Acts on certain fields type.
   *
   * Tries to add the field to the list of fields known to the index specified
   * by 'acquia_cms.search_index' third-party setting.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $field_storage
   *   The new field's storage definition.
   */
  public function addFields(FieldStorageConfigInterface $field_storage) {
    $index = $this->loadIndexFromSettings($field_storage);
    if (empty($index)) {
      return;
    }

    $field_name = $field_storage->getName();

    // Bail out if the field already exists on the index.
    if ($index->getField($field_name)) {
      return;
    }

    $field_type = $field_storage->getType();
    // Field storages don't normally have a human-readable label, so allow it to
    // provide one in its third-party settings.
    $field_label = $field_storage->getThirdPartySetting('acquia_cms_common', 'search_label') ?: $field_storage->getLabel();

    $data_source_id = 'entity:' . $field_storage->getTargetEntityTypeId();
    // This will throw an exception if the data source doesn't exist, so this
    // is really just a way to prevent the field from using an invalid data
    // source.
    $data_source_id = $index->getDatasource($data_source_id)->getPluginId();

    $type = '';
    switch ($field_type) {
      case 'string':
      case 'email':
      case 'telephone':
      case 'address':
        $type = 'string';
        break;

      case 'datetime':
        $type = 'date';
        break;

      case 'text_with_summary':
        $type = 'text';
        break;
    }

    // Add the referenced term's ID to the index.
    $field = $this->fieldsHelper->createField($index, $field_name)
      ->setLabel($field_label)
      ->setDatasourceId($data_source_id)
      ->setPropertyPath($field_name)
      ->setType($type);
    $index->addField($field);
    $this->indexStorage->save($index);
  }

  /**
   * Load a search index from the 'acquia_cms.search_index' third-party setting.
   *
   * @param \Drupal\Core\Config\Entity\ThirdPartySettingsInterface $object
   *   The object which carries the third-party setting (e.g., a config entity).
   *
   * @return \Drupal\search_api\IndexInterface
   *   The search index named in the third-party setting, or NULL if the index
   *   doesn't exist or a config sync is in progress.
   */
  private function loadIndexFromSettings(ThirdPartySettingsInterface $object) : ?IndexInterface {
    // We don't want to do any secondary config writes during a config sync,
    // since that can have major, unintentional side effects.
    if ($this->configInstaller->isSyncing()) {
      return NULL;
    }
    $index = $object->getThirdPartySetting('acquia_cms_common', 'search_index');
    return $index ? $this->indexStorage->load($index) : NULL;
  }

  /**
   * Update views display_options's style.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateViewDisplayOptionsStyle($view_name, $display_id = 'default', $views_template = NULL) {
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = $this->viewStorage->load($view_name);
    if (empty($view)) {
      return;
    }
    $display = &$view->getDisplay($display_id);
    $style_type = $display['display_options']['style']['type'];
    if ($style_type !== 'cohesion_layout') {
      $display['display_options']['style']['type'] = 'cohesion_layout';
      $display['display_options']['style']['options'] = [
        'views_template' => $views_template ?? 'view_tpl_' . $view_name,
        'master_template' => 'master_template_boxed',
      ];
      $this->viewStorage->save($view);
    }
  }

}
