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
      $route->setDefault('_controller', '\Drupal\next\Controller\SitePreviewController::nodePreview');
    }
  }

}
