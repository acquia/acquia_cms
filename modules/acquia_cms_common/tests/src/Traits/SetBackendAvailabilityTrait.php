<?php

namespace Drupal\Tests\acquia_cms_common\Traits;

use Drupal\Component\Utility\NestedArray;
use Drupal\views\Entity\View;

/**
 * Provides a framework for setting the backend availability.
 */
trait SetBackendAvailabilityTrait {

  /**
   * Toggles the availability of the search backend.
   *
   * This is used to test the fallback view displayed by the listing page if the
   * search backend is down.
   *
   * @param bool $is_available
   *   If TRUE, the view_fallback handler will behave normally. If FALSE, the
   *   handler will behave as if the search backend is down, in order to
   *   facilitate testing that the fallback view appears and looks the way we
   *   expect it to.
   */
  private function setBackendAvailability(bool $is_available) : void {
    $view = $this->getView();
    $display = &$view->getDisplay('default');
    $key = ['display_options', 'empty', 'view_fallback', 'simulate_unavailable'];
    if ($is_available) {
      NestedArray::unsetValue($display, $key);
    }
    else {
      NestedArray::setValue($display, $key, TRUE);
    }
    $view->save();
  }

  /**
   * Returns the view entity for the listing page.
   *
   * @return \Drupal\views\Entity\View
   *   The listing page's view.
   */
  abstract protected function getView() : View;

}
