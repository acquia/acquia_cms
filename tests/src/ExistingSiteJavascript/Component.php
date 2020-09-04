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
  public function drop(string $label) : self {
    $dropzone = $this->waitForElementVisible('css', '.coh-layout-canvas-list-dropzone', $this);
    $dropzone->mouseOver();
    $this->waitForElementVisible('css', '.coh-add-btn', $dropzone)->press();
    $this->waitForElementBrowser()->select($label)->close();
    return $this->assertComponent($label);
  }

  /**
   * Opens the modal edit form for this component.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The modal edit form for the component.
   */
  public function edit() : ElementInterface {
    $this->pressAriaButton('More actions');
    $this->waitForElementVisible('css', '.coh-layout-canvas-utils-dropdown-menu .coh-edit-btn')->press();

    // Wait for the form wrapper to appear...
    $form = $this->waitForElementVisible('css', '.coh-layout-canvas-settings');
    // ...then wait the form wrapper to load the actual settings form.
    $this->waitForElementVisible('css', 'coh-component-form', $form);
    return $form;
  }

}
