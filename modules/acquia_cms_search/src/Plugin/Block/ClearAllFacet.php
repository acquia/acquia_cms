<?php

namespace Drupal\acquia_cms_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'ClearAllFacet' Block.
 *
 * @Block(
 *   id = "clearall_facet",
 *   admin_label = @Translation("Clear All Facets"),
 * )
 */
class ClearAllFacet extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routematch;

  /**
   * The config factory object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * Constructs the ClearAllFacet Block.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentRouteMatch $route_match, RequestStack $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routematch = $route_match;
    $this->blockmanager = $block_manager;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Check if any facets_query is present in the url. If yes then display the
    // reset link.
    if ($this->routematch->getParameter('facets_query')) {
      $url = $this->routematch->getRouteName();
      $link = Link::createFromRoute($this->t('Clear filter(s)'), $url, $this->request->getCurrentRequest()->query->all());

      return $link->toRenderable();
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url']);
  }
}
