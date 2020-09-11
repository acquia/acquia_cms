<?php

namespace Drupal\acquia_cms_tour\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines a route controller providing a simple tour of Acquia CMS.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class TourController extends ControllerBase {

  /**
   * Returns a renderable array for a tour page.
   */
  public function build() {
    return [
      '#theme' => 'tour',
    ];
  }

}
