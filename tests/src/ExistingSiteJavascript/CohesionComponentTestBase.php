<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Behat\Mink\Element\ElementInterface;

/**
 * Base class for testing Acquia CMS's Cohesion Components.
 */
abstract class CohesionComponentTestBase extends CohesionTestBase {

  /**
   * Adds a component to a layout canvas.
   *
   * @param \Behat\Mink\Element\ElementInterface $canvas
   *   The layout canvas element.
   * @param string $label
   *   The component label.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The component that has been added to the layout canvas.
   */
  protected function addComponent(ElementInterface $canvas, string $label) : ElementInterface {
    $this->pressAriaButton($canvas, 'Add content');
    $this->selectComponentInElementBrowser($label);
    return $this->assertComponent($canvas, $label);
  }

  /**
   * Adds a component inside another component's dropzone.
   *
   * @param \Behat\Mink\Element\ElementInterface $container
   *   The containing component, which contains a dropzone.
   * @param string $label
   *   The label of the component to add.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The component added to the dropzone.
   */
  protected function addComponentToDropZone(ElementInterface $container, string $label) : ElementInterface {
    $dropzone = $this->waitForElementVisible('css', '.coh-layout-canvas-list-dropzone', $container);
    $dropzone->mouseOver();
    $this->waitForElementVisible('css', '.coh-add-btn', $dropzone)->press();
    $this->selectComponentInElementBrowser($label);
    return $this->assertComponent($container, $label);
  }

  /**
   * Selects a component from the element browser.
   *
   * @param string $label
   *   The label of the component to select.
   */
  private function selectComponentInElementBrowser(string $label) : void {
    $element_browser = $this->waitForElementBrowser();

    $selector = sprintf('.coh-layout-canvas-list-item[data-title="%s"]', $label);
    $this->waitForElementVisible('css', $selector, $element_browser)->doubleClick();
    $this->pressAriaButton($element_browser, 'Close sidebar browser');
  }

  /**
   * Opens the modal edit form for a component.
   *
   * @param \Behat\Mink\Element\ElementInterface $component
   *   The component element.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The modal edit form for the component.
   */
  protected function editComponent(ElementInterface $component) : ElementInterface {
    $this->pressAriaButton($component, 'More actions');
    $this->waitForElementVisible('css', '.coh-layout-canvas-utils-dropdown-menu .coh-edit-btn')->press();

    // Wait for the form wrapper to appear...
    $form = $this->waitForElementVisible('css', '.coh-layout-canvas-settings');
    // ...then wait the form wrapper to load the actual settings form.
    $this->waitForElementVisible('css', 'coh-component-form', $form);
    return $form;
  }

  /**
   * Tries to open the edit form for a component in the administrative UI.
   *
   * @param string $group
   *   The group to which the component belongs.
   * @param string $label
   *   The label of the component.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The component's administrative edit form.
   */
  protected function editComponentDefinition(string $group, string $label) : ElementInterface {
    $assert_session = $this->assertSession();

    // Ensure that the component's group container is open.
    $group = $assert_session->elementExists('css', "details > summary:contains($group)");
    if ($group->getParent()->hasAttribute('open') === FALSE) {
      $group->click();
    }

    $assert_session->elementExists('css', 'tr:contains("' . $label . '")', $group->getParent())
      ->clickLink('Edit');

    return $this->waitForElementVisible('css', '.cohesion-component-edit-form');
  }

}
