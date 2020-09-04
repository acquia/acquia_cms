<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Wrapper class for interacting with Cohesion's element browser.
 */
final class ElementBrowser extends CohesionElement {

  /**
   * Selects a component or helper in the element browser.
   *
   * @param string $label
   *   The label of the component or helper to add.
   *
   * @return $this
   *   The called object, for chaining.
   */
  public function select(string $label) : self {
    $selector = sprintf('.coh-layout-canvas-list-item[data-title="%s"]', $label);
    $this->waitForElementVisible('css', $selector, $this)->doubleClick();
    return $this;
  }

  /**
   * Closes the element browser.
   */
  public function close() : void {
    $this->pressAriaButton('Close sidebar browser');
  }

  /**
   * Switches to a particular group of elements.
   *
   * @param string $group
   *   The label of the group to switch to.
   *
   * @return $this
   *   The called object, for chaining.
   */
  public function switchToGroup(string $group) : self {
    $this->waitForElementVisible('css', '.coh-layout-canvas-menu');
    $this->waitForElementVisible('css', '.coh-nav-dropdown')->click();
    $this->waitForElementVisible('css', "a.nav-link:contains('$group')")->press();
    return $this;
  }

}
