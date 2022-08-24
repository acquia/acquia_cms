<?php

namespace Drupal\acquia_cms_headless_ui\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class to alter the route controller.
 */
class SitePreviewRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change the controller for the latest version tab to site-preview.
    if ($route = $collection->get("entity.node.latest_version")) {
      $defaults = $route->getDefaults();
      unset($defaults['_title_callback']);
      $route->setPath('/node/{node}/site-preview');
    }
  }

}
