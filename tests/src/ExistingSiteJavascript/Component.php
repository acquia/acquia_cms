<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Behat\Mink\Element\ElementInterface;

/**
 * A wrapper object for interacting with Cohesion components.
 */
final class Component extends CohesionElement {

  /**
   * Adds a component inside this component's dropzone.
   *
   * @param string $label
   *   The label of the component to add.
   *
   * @return self
   *   The component that was added to the dropzone.
   */
  public function drop(string $label): self {
    /** @var \Behat\Mink\Element\NodeElement $dropzone */
    $dropzone = $this->waitForElementVisible('css', '.ssa-layout-canvas-list-item-type-container', $this);
    $dropzone->mouseOver();
    $this->waitForElementVisible('css', '.ssa-btn-canvas-node', $dropzone);
    $this->pressAriaButton('Insert here');
    $this->waitForElementBrowser()->select($label)->close();
    return $this->assertComponent($label);
  }

  /**
   * Opens the modal edit form for this component.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The modal edit form for the component.
   */
  public function edit(): ElementInterface {
    $this->pressAriaButton('More actions');
    /** @var \Behat\Mink\Element\NodeElement $dropdownToggle */
    $dropdownToggle = $this->waitForElementVisible('css', '.ssa-dropdown-menu .ssa-dropdown-item');
    $dropdownToggle->press();

    // Wait for the sidebar form to appear.
    $this->waitForElementVisible('css', 'form.ssa-sidebar-component');

    // Wait for the form wrapper to appear.
    return $this->waitForElementVisible('css', '.ssa-component-form--inner');
    ;
  }

}
