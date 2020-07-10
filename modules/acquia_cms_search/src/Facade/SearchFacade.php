<?php

namespace Drupal\acquia_cms_search\Facade;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
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
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * MetatagFacade constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
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
      $config = $this->configFactory->getEditable('search_api.index.' . $search_index);
      $data_sources = $config->get('datasource_settings');
      $data_sources['entity:node']['bundles']['selected'][] = $node_type->id();
      $config->set('datasource_settings', $data_sources);
      $config->save();
    }
  }

}
