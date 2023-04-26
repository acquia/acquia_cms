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
  public function select(string $label): self {
    $selector = sprintf('#ssa-sidebar-browser [data-ssa-name="%s"]', $label);
    $item = $this->waitForElementVisible('css', $selector, $this);
    $this->pressAriaButton('Add to canvas', $item);

    // Let's wait for component/helper to be added in the layout
    // canvas field before we close the sidebar.
    $this->waitForElementVisible('css', '.ssa-icon-plus-circle', $item);
    return $this;
  }

  /**
   * Closes the element browser.
   */
  public function close(): void {
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
  public function switchToGroup(string $group): self {
    /** @var \Behat\Mink\Element\NodeElement $dropdownToggle */
    $dropdownToggle = $this->waitForElementVisible('css', '.ssa-dropdown-toggle');
    $dropdownToggle->press();
    /** @var \Behat\Mink\Element\NodeElement $groupToggle */
    $groupToggle = $this->waitForElementVisible('css', ".ssa-dropdown-item:contains('$group')");
    $groupToggle->press();
    return $this;
  }

}
