<?php

namespace Drupal\acquia_cms_common\Facade;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\node\NodeTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a facade for integrating with Metatag.
 */
final class MetatagFacade implements ContainerInjectionInterface {

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
   * Tries to enable specific metatag tag types for the new node type, as
   * specified by the 'acquia_cms.metatag.tag_types' third-party setting.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The new node type.
   */
  public function addNodeType(NodeTypeInterface $node_type) {
    $settings = $node_type->getThirdPartySetting('acquia_cms', 'metatag', []);

    if (isset($settings['tag_types'])) {
      $key = 'entity_type_groups.node.' . $node_type->id();

      $this->configFactory->getEditable('metatag.settings')
        ->set($key, $settings['tag_types'])
        ->save();
    }
  }

}
