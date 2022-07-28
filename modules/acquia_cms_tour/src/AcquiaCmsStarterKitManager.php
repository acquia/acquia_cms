<?php

namespace Drupal\acquia_cms_tour;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * AcquiaCmsStarterKit plugin manager.
 */
class AcquiaCmsStarterKitManager extends DefaultPluginManager {

  /**
   * Constructs a AcquiaCmsStarterKitManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
  \Traversable $namespaces,
  CacheBackendInterface $cache_backend,
  ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/AcquiaCmsStarterKit',
      $namespaces,
      $module_handler,
      'Drupal\acquia_cms_tour\AcquiaCmsStarterKitInterface',
      'Drupal\acquia_cms_tour\Annotation\AcquiaCmsStarterKit'
    );
    $this->alterInfo('acquia_cms_starter_kit_info');
    $this->setCacheBackend($cache_backend, 'acquia_cms_starter_kit_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions(): array {
    // Sort plugin by its weight.
    $definitions = parent::findDefinitions();
    uasort($definitions, [
      'Drupal\Component\Utility\SortArray',
      'sortByWeightElement',
    ]);
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getTourManagerPlugin(): array {
    $options = [];
    foreach ($this->getDefinitions() as $definition) {
      $options[$definition['id']] = $definition;
    }
    return $options;
  }

}
