<?php

namespace Drupal\acquia_cms_search\Plugin\views\cache;

use Drupal\views\Plugin\views\cache\None;
use Drupal\views\ResultRow;

/**
 * Provides backward compatibility for search_api_none views cache type.
 *
 * This views_cache plugin was introduced in search_api:8.x-1.33, & search_api
 * dropped support for Drupal Core 9.x starting from search_api:8.x-1.31.
 *
 * @see https://www.drupal.org/project/search_api/issues/3423063.
 *
 * @ingroup views_cache_plugins
 *
 * @ViewsCache(
 *   id = "search_api_none_bc",
 *   title = @Translation("Search API (none)"),
 *   help = @Translation("No caching of Views data.")
 * )
 */
class SearchApiNoneCacheBC extends None {

  /**
   * {@inheritdoc}
   */
  public function getRowId(ResultRow $row) {
    return $row->search_api_id;
  }

}
