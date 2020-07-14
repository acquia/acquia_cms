<?php

namespace Drupal\acquia_cms_tour\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines a route controller providing a simple tour of Acquia CMS.
 */
final class TourController extends ControllerBase {

  /**
   * Returns a renderable array for a tour page.
   */
  public function tour() {
    return [
      '#markup' => $this->t('Hello World!'),
    ];
  }

}
