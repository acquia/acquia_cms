<?php

namespace Drupal\acquia_cms_common\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for default HTTP 4xx responses.
 */
class CustomHttp4xxController extends ControllerBase {

  /**
   * The default 4xx error content.
   *
   * @return array
   *   A render array containing the message to display for 4xx errors.
   */
  public function on4xx() {
    return [
      '#markup' => $this->t('A client error happened'),
    ];
  }

  /**
   * The default 401 content.
   *
   * @return array
   *   A render array containing the message to display for 401 pages.
   */
  public function on401() {
    return [
      '#markup' => $this->t('Please log in to access this page.'),
    ];
  }

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
