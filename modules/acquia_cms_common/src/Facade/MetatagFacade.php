<?php

namespace Drupal\acquia_cms_common\Facade;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\node\NodeTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a facade for integrating with Metatag.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class MetatagFacade implements ContainerInjectionInterface {

  /**
   * The config installer service.
   *
   * @var \Drupal\Core\Config\ConfigInstallerInterface
   */
  private $configInstaller;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * MetatagFacade constructor.
   *
   * @param \Drupal\Core\Config\ConfigInstallerInterface $config_installer
   *   The config installer service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigInstallerInterface $config_installer, ConfigFactoryInterface $config_factory) {
    $this->configInstaller = $config_installer;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.installer'),
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
    // We don't want to do any secondary config writes during a config sync,
    // since that can have major, unintentional side effects.
    if ($this->configInstaller->isSyncing()) {
      return;
    }

    $settings = $node_type->getThirdPartySetting('acquia_cms_common', 'metatag', []);
    if (isset($settings['tag_types'])) {
      $key = 'entity_type_groups.node.' . $node_type->id();

      $this->configFactory->getEditable('metatag.settings')
        ->set($key, $settings['tag_types'])
        ->save();
    }
  }

}
