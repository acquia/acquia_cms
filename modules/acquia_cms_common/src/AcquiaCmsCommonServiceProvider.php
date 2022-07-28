<?php

namespace Drupal\acquia_cms_common;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Defines a service provider for Acquia CMS Common.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class AcquiaCmsCommonServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');

    if (isset($modules['facets'])) {
      $container->register('acquia_cms_common.breadcrumb.subtype')
        ->setClass(SubtypeBreadcrumb::class)
        ->setArguments([
          new Reference('entity_type.manager'),
          new Reference('plugin.manager.facets.url_processor'),
        ])
        ->addTag('breadcrumb_builder', [
          'priority' => 10,
        ]);
    }
  }

}
