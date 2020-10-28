<?php

namespace Drupal\acquia_cms_search\Plugin\views\area;

use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\views\Plugin\views\area\View;

/**
 * Defines a view area handler that short-circuits if the search server is up.
 *
 * This handler is only useful on Search API-based views. If the search server
 * that powers the view is available, this will not display anything. If the
 * server is down, it will display a view. If the view is not powered by Search
 * API, this handler will not display anything.
 *
 * @ViewsArea("view_fallback")
 */
final class FallbackView extends View {

  /**
   * {@inheritdoc}
   */
  public function preQuery() {
    $query = $this->view->getQuery();
    $simulate_unavailable = $this->options['simulate_unavailable'] ?? FALSE;

    if ($query instanceof SearchApiQuery && $simulate_unavailable) {
      $query->abort();
    }
    parent::preQuery();
  }

}
