<?php

namespace Drupal\acquia_cms_search\Plugin\views\query;

use Drupal\Core\Cache\Cache;
use Drupal\search_api\Plugin\views\query\SearchApiQuery as BaseSearchApiQuery;

/**
 * Defines additional behavior for the standard Search API Views query handler.
 */
final class SearchApiQuery extends BaseSearchApiQuery {

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // If using Facets Pretty Paths, we need to be sure to use the url.path
    // cache context, which views do not normally use.
    return Cache::mergeContexts(['url.path'], parent::getCacheContexts());
  }

}
