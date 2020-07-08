<?php

namespace Drupal\Tests\acquia_cms\FunctionalJavascript;

use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Element\NodeElement;
use PHPUnit\Framework\Assert;

/**
 * Provides a utility class for interacting with a Cohesion layout canvas.
 */
class LayoutCanvas extends NodeElement {

  /**
   * Asserts that a component is present in the layout canvas.
   *
   * @param string $label
   *   The component label.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The component element.
   */
  public function assertComponent(string $label) {
    return $this->assertVisibleElement('css', ".coh-layout-canvas-list-item[data-type='$label']", $this);
  }

  /**
   * Adds a component to the layout canvas.
   *
   * @param string $label
   *   The label of the component to add.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The component element.
   */
  public function addComponent(string $label) {
    $this->pressAriaButton('Add content');

    $component_list = $this->getComponentList();
    Assert::assertArrayHasKey($label, $component_list);
    $component_list[$label]->doubleClick();

    return $this->assertComponent($label);
  }

  /**
   * Edits a component in the layout canvas.
   *
   * @param \Behat\Mink\Element\NodeElement $component
   *   The component element.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The edit form for the component.
   */
  public function editComponent(NodeElement $component) {
    $component->doubleClick();
    $edit_form = $this->assertVisibleElement('css', '.coh-layout-canvas-settings', $this->getPage());
    $this->assertVisibleElement('css', 'coh-component-form', $edit_form);
    return $edit_form;
  }

  /**
   * Returns the component list from the sidebar browser.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The components listed in the sidebar browser, keyed by label.
   */
  private function getComponentList() {
    $elements = $this->waitForVisibleElements('css', '.coh-element-browser-modal .coh-layout-canvas-list-item', $this->getPage());
    Assert::assertNotEmpty($elements);

    $component_list = [];
    foreach ($elements as $element) {
      $text = $element->getText();
      $component_list[$text] = $element;
    }
    return $component_list;
  }

  /**
   * Finds a button by its ARIA label and presses it.
   *
   * @param string $label
   *   The button's aria-label attribute value.
   */
  private function pressAriaButton(string $label) {
    $selector = sprintf('button[aria-label="%s"]', $label);
    $button = $this->find('css', $selector);
    Assert::assertInstanceOf(NodeElement::class, $button);
    $button->press();
  }

  /**
   * Waits for an element to become visible.
   *
   * @param string $selector
   *   The selector for the element, e.g. 'css', 'xpath', etc.
   * @param mixed $locator
   *   The element locator, e.g. a CSS selector, XPath query, etc.
   * @param \Behat\Mink\Element\ElementInterface $scope
   *   The element which contains the element which should become visible.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The element which has become visible.
   */
  private function assertVisibleElement(string $selector, $locator, ElementInterface $scope) {
    $elements = $this->waitForVisibleElements($selector, $locator, $scope);
    Assert::assertNotEmpty($elements);
    return reset($elements);
  }

  /**
   * Waits for a set of elements to become visible.
   *
   * @param string $selector
   *   The selector for the elements, e.g. 'css', 'xpath', etc.
   * @param mixed $locator
   *   The elements' locator, e.g. a CSS selector, XPath query, etc.
   * @param \Behat\Mink\Element\ElementInterface $scope
   *   The element which contains the elements which should become visible.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The elements which have become visible.
   */
  private function waitForVisibleElements(string $selector, $locator, ElementInterface $scope) {
    return $scope->waitFor(10, function (ElementInterface $scope) use ($selector, $locator) {
      $elements = $scope->findAll($selector, $locator);
      return $elements && reset($elements)->isVisible() ? $elements : FALSE;
    });
  }

  /**
   * Returns the page element.
   *
   * @return \Behat\Mink\Element\DocumentElement
   *   The page element.
   */
  private function getPage() {
    return $this->getSession()->getPage();
  }

}
