<?php

namespace Drupal\acquia_cms_common\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for default HTTP 403 and 404 responses.
 */
class CustomHttp4xxController extends ControllerBase {

  /**
   * The default 403 content.
   *
   * @return array
   *   A render array containing the message to display for 403 pages.
   */
  public function on403() {
    return [
      '#theme' => 'page__system__403',
    ];
  }

  /**
   * The default 404 content.
   *
   * @return array
   *   A render array containing the message to display for 404 pages.
   */
  public function on404() {
    return [
      '#theme' => 'page__system__404',
    ];
  }

}
