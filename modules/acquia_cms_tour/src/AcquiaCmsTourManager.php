<?php

namespace Drupal\acquia_cms_tour;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * AcquiaCmsTour plugin manager.
 */
class AcquiaCmsTourManager extends DefaultPluginManager {

  /**
   * Constructs AcquiaCmsTourManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/AcquiaCmsTour',
      $namespaces,
      $module_handler,
      'Drupal\acquia_cms_tour\AcquiaCmsTourInterface',
      'Drupal\acquia_cms_tour\Annotation\AcquiaCmsTour'
    );
    $this->alterInfo('acquia_cms_tour_info');
    $this->setCacheBackend($cache_backend, 'acquia_cms_tour_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getAllForms() {
    $options = [];
    // @todo write code to sort the getDefinitions() in the order of plugin weight.
    foreach ($this->getDefinitions() as $definition) {
      $options[$definition['id']] = \Drupal::formBuilder()->getForm('\Drupal\acquia_cms_site_studio\Plugin\AcquiaCmsTour\SiteStudioCoreForm');
    }
    return $options;
  }

}
