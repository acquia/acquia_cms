<?php

namespace Drupal\acquia_cms_common\Facade;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\easy_breadcrumb\EasyBreadcrumbBuilder;
use Drupal\facets\Result\Result;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a facade for modifying breadcrumbs.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class BreadcrumbFacade implements ContainerInjectionInterface {

  /**
   * The facet entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $facetStorage;

  /**
   * The Facets URL processor plugin manager service.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  private $urlProcessorManager;

  /**
   * BreadcrumbFacade constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $facet_storage
   *   The facet entity storage handler.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $url_processor_manager
   *   The Facets URL processor plugin manager service.
   */
  public function __construct(EntityStorageInterface $facet_storage, PluginManagerInterface $url_processor_manager) {
    $this->facetStorage = $facet_storage;
    $this->urlProcessorManager = $url_processor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('facets_facet'),
      $container->get('plugin.manager.facets.url_processor')
    );
  }

  /**
   * Alters the breadcrumb trail created by Easy Breadcrumb.
   *
   * If viewing a node that has a sub-type, this will append a link to the
   * sub-type page to the breadcrumb.
   *
   * @param \Drupal\Core\Breadcrumb\Breadcrumb $breadcrumb
   *   The breadcrumb trail to modify.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param array $context
   *   Additional information passed to hook_system_breadcrumb_alter().
   */
  public function alter(Breadcrumb $breadcrumb, RouteMatchInterface $route_match, array $context) : void {
    if ($context['builder'] instanceof EasyBreadcrumbBuilder && $route_match->getRouteName() === 'entity.node.canonical') {
      $node = $route_match->getParameter('node');

      $link = $this->getSubtypeLink($node);
      if ($link) {
        $breadcrumb->addLink($link);
      }
    }
  }

  /**
   * Generates a sub-type link for a node.
   *
   * This requires the node's type to have third-party settings in its
   * 'acquia_cms.subtype' key, defining the name of the field that references
   * the sub-type taxonomy term, and the machine name of the facet used on the
   * sub-type page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node for which to generate a sub-type link.
   *
   * @return \Drupal\Core\Link|null
   *   A link to the sub-type page, or NULL if there is not enough information
   *   available to generate the link.
   */
  private function getSubtypeLink(NodeInterface $node) : ?Link {
    // If the node type defines its sub-type field and the related facet, use
    // that to generate the breadcrumb link.
    $settings = $node->type->entity->getThirdPartySetting('acquia_cms', 'subtype', []);

    if (empty($settings) || !$node->hasField($settings['field'])) {
      return NULL;
    }

    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = $this->facetStorage->load($settings['facet']);
    if (empty($facet)) {
      return NULL;
    }

    $subtype_field = $node->get($settings['field']);
    if ($subtype_field->isEmpty()) {
      return NULL;
    }

    /** @var \Drupal\taxonomy\TermInterface $subtype */
    $subtype = $subtype_field->entity;
    // Invoke the URL processor associated with this facet's source to generate
    // a URL object pointing to the sub-type page.
    $url_processor = $facet->getFacetSourceConfig()->getUrlProcessorName();
    /** @var \Drupal\facets\UrlProcessor\UrlProcessorInterface $url_processor */
    $results = $this->urlProcessorManager->createInstance($url_processor, ['facet' => $facet])
      ->buildUrls($facet, [
        new Result($facet, $subtype->id(), $subtype->label(), 1),
      ]);

    return Link::fromTextAndUrl($subtype->label(), reset($results)->getUrl());
  }

}
