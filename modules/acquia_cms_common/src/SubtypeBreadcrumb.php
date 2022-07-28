<?php

namespace Drupal\acquia_cms_common;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\facets\FacetInterface;
use Drupal\facets\FacetSource\SearchApiFacetSourceInterface;
use Drupal\facets\Result\Result;
use Drupal\node\NodeTypeInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Defines a breadcrumb builder for content types with faceted sub-types.
 *
 * This only works on node pages. It attempts to read the 'acquia_cms.subtype'
 * section from the third-party settings of the type of node being viewed, which
 * is expected to contain the name of the sub-type taxonomy term reference field
 * and the name of the associated facet on the content type's listing page.
 * Using these two pieces of information, it builds two links: one to the
 * content type's listing page, with no facets applied; and one to the content
 * type's listing page, with the sub-type facet applied.
 *
 * Because this uses the underlying data structures and relationships of the
 * node being viewed, the generated breadcrumb is NOT affected by the current
 * URL path.
 *
 * Since this breadcrumb builder relies on facets, it is only registered in the
 * container if Facets is installed.
 *
 * @see \Drupal\acquia_cms_common\AcquiaCmsCommonServiceProvider::register()
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class SubtypeBreadcrumb implements BreadcrumbBuilderInterface {

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
   * SubtypeBreadcrumb constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $url_processor_manager
   *   The Facets URL processor plugin manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PluginManagerInterface $url_processor_manager) {
    $this->facetStorage = $entity_type_manager->getStorage('facets_facet');
    $this->urlProcessorManager = $url_processor_manager;
  }

  /**
   * Returns the sub-type settings, if available.
   *
   * Sub-type settings are an array with the following keys:
   * - 'field': The machine name of the field which references the sub-type
   *   taxonomy term.
   * - 'facet': The machine name of the facet which is associated with the
   *   field.
   *
   * This implementation will pull these settings from the 'acquia_cms.subtype'
   * third-party settings of the type of node being viewed.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return array
   *   The sub-type settings for the type of node being viewed, if the node type
   *   defines those settings. If it does not, or we are not viewing a node
   *   page, an empty array is returned.
   */
  private function getSettings(RouteMatchInterface $route_match) : array {
    if ($route_match->getRouteName() === 'entity.node.canonical') {
      /** @var \Drupal\node\NodeInterface $node */
      $node = $route_match->getParameter('node');
      return $node->type->entity->getThirdPartySetting('acquia_cms_common', 'subtype', []);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $settings = $this->getSettings($route_match);

    if (isset($settings['facet'])) {
      $facet = $this->facetStorage->load($settings['facet']);
      return isset($facet);
    }
    // If no sub-type settings are available, we cannot generate a breadcrumb.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['route']);

    $settings = $this->getSettings($route_match);
    /** @var \Drupal\node\NodeInterface $node */
    $node = $route_match->getParameter('node');
    $breadcrumb->addCacheableDependency($node);

    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = $this->facetStorage->load($settings['facet']);
    $breadcrumb->addCacheableDependency($facet);

    $link = $this->getListPageLink($node->type->entity, $facet);
    $breadcrumb->addLink($link);

    if (isset($settings['field']) && $node->hasField($settings['field'])) {
      $sub_type = $node->get($settings['field'])->entity;

      if ($sub_type) {
        $link = $this->getSubTypeLink($sub_type, $facet);
        $breadcrumb->addLink($link);
      }
    }

    // Add the unlinked node title to the breadcrumb.
    $link = Link::fromTextAndUrl($node->label(), Url::fromRoute('<none>'));
    $breadcrumb->addLink($link);

    return $breadcrumb;
  }

  /**
   * Builds a link to the content type list page.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The type of node being viewed.
   * @param \Drupal\facets\FacetInterface $facet
   *   The sub-type facet.
   *
   * @return \Drupal\Core\Link
   *   A link to the content type's list page, without any sub-type facet
   *   applied.
   */
  private function getListPageLink(NodeTypeInterface $node_type, FacetInterface $facet) : Link {
    $facet_source = $facet->getFacetSource();

    if ($facet_source instanceof SearchApiFacetSourceInterface) {
      $views_display = $facet_source->getViewsDisplay();
      // If the facet is based on a view, use the view display's label.
      // Otherwise, fall back to the facet display's label, which may be a lot
      // less friendly.
      $text = $views_display
        ? $views_display->getTitle()
        : $facet_source->getDisplay()->label();
    }
    else {
      $text = $node_type->label();
    }

    $url = Url::fromUri('internal:' . $facet_source->getPath());

    return Link::fromTextAndUrl($text, $url);
  }

  /**
   * Builds a link to the content type list page, with a sub-type facet applied.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The sub-type taxonomy term.
   * @param \Drupal\facets\FacetInterface $facet
   *   The sub-type facet.
   *
   * @return \Drupal\Core\Link
   *   A link to the content type's list page, with the sub-type facet applied.
   */
  private function getSubTypeLink(TermInterface $term, FacetInterface $facet) : Link {
    $text = $term->label();

    // Invoke the URL processor associated with the facet's source to generate
    // a URL object pointing to the sub-type page.
    $url_processor = $facet->getFacetSourceConfig()->getUrlProcessorName();
    /** @var \Drupal\facets\UrlProcessor\UrlProcessorInterface $url_processor */
    $url_processor = $this->urlProcessorManager->createInstance($url_processor, [
      'facet' => $facet,
    ]);

    $results = [
      new Result($facet, $term->id(), $text, 1),
    ];
    $results = $url_processor->buildUrls($facet, $results);

    return Link::fromTextAndUrl($text, reset($results)->getUrl());
  }

}
