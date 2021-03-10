<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * A wrapper object for interacting with Cohesion's search container.
 */
final class Search extends CohesionElement {

  /**
   * Shows the search form.
   */
  public function showSearch() {
    $this->pressAriaButton('Show search');
  }

}
