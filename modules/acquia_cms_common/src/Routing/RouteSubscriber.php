<?php

namespace Drupal\acquia_cms_common\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('system.404')) {
      $route->setDefaults([
        '_controller' => '\Drupal\acquia_cms_common\Controller\CustomHttp4xxController::on404',
      ]);
    }

    if ($route = $collection->get('system.403')) {
      $route->setDefaults([
        '_controller' => '\Drupal\acquia_cms_common\Controller\CustomHttp4xxController::on403',
      ]);
    }
  }

}
