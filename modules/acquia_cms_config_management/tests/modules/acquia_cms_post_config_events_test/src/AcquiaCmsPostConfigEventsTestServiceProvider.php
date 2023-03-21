<?php

namespace Drupal\acquia_cms_post_config_events_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Defines a service provider for Acquia CMS Common.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class AcquiaCmsPostConfigEventsTestServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    /** @var \Symfony\Component\DependencyInjection\Definition $definition */
    $definition = $container->getDefinition('acquia_cms_config_management.post_config_export_acquia');
    $definition->setClass('Drupal\acquia_cms_post_config_events_test\EventSubscriber\TestAcquiaPostConfigExport');
  }

}
