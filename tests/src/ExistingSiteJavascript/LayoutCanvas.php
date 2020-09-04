<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Wrapper class for interacting with layout canvases.
 */
final class LayoutCanvas extends CohesionElement {

  /**
   * Adds a component or helper to the layout canvas.
   *
   * @param string $label
   *   The label of the component or helper to add.
   *
   * @return \Drupal\Tests\acquia_cms\ExistingSiteJavascript\Component
   *   The added component.
   */
  public function add(string $label) : Component {
    $this->pressAriaButton('Add content');
    $this->waitForElementBrowser()->select($label)->close();
    return $this->assertComponent($label);
  }

  /**
   * Adds a helper to the layout canvas.
   *
   * @param string $label
   *   The helper label.
   * @param string[] $expected_components
   *   The labels of the components that will be added by the helper.
   */
  public function addHelper(string $label, array $expected_components = []) : void {
    $this->pressAriaButton('Add content');
    $this->waitForElementBrowser()
      ->switchToGroup('Helpers')
      ->select($label)
      ->close();

    array_walk($expected_components, [$this, 'assertComponent']);
  }

}
