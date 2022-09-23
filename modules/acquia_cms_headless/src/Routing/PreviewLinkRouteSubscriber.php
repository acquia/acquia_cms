<?php

namespace Drupal\acquia_cms_headless\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class to alter the route controller.
 */
class PreviewLinkRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Add the requirement to access the link preview.
    if ($route = $collection->get("entity.node.headless_preview")) {
      $route->setRequirements([
        '_preview_link_access_check' => 'TRUE',
      ]);
    }
  }

}
