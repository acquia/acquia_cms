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
  public function edit() : ElementInterface {
    $this->pressAriaButton('More actions');
    $this->waitForElementVisible('css', '.ssa-dropdown-menu .ssa-dropdown-item')->press();

    $this->getIframeElements();

    // Wait for the form wrapper to appear...
    $form = $this->waitForElementVisible('css', '.coh-layout-canvas-settings');
    // ...then wait the form wrapper to load the actual settings form.
    $this->waitForElementVisible('css', 'coh-component-form', $form);
    return $form;
  }

  /**
   * In site studio 6.8 onwards component edit page open in iframe.
   */
  public function getIframeElements() {
    $selector = 'iframe[title="Edit component"]';
    $frame = $this->waitForElementVisible('css', $selector, $this->session->getPage());
    $name = $frame->getAttribute('name');
    if (empty($name)) {
      $name = 'edit_component_iframe';
      $this->session->executeScript("document.querySelector('$selector').setAttribute('name', '$name')");
    }
    $this->session->switchToIFrame($name);
  }

}
