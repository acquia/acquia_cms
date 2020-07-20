<?php

namespace Drupal\Tests\acquia_cms_common\ExistingSiteJavascript;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Behat\Mink\Element\ElementInterface;
use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase;

/**
 * Defines a base class for testing Acquia CMS's Cohesion integration.
 */
abstract class CohesionTestBase extends ExistingSiteSelenium2DriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // @todo Remove this check when Acquia Cloud IDEs support running functional
    // JavaScript tests.
    if (AcquiaDrupalEnvironmentDetector::isAhIdeEnv()) {
      $this->markTestSkipped('This test cannot run in an Acquia Cloud IDE.');
    }
    parent::setUp();
  }

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
    $element_browser = $this->waitForElementBrowser();

    $selector = sprintf('.coh-layout-canvas-list-item[data-title="%s"]', $label);
    $this->waitForElementVisible('css', $selector, $element_browser)->doubleClick();
    $this->pressAriaButton($element_browser, 'Close sidebar browser');

    return $this->assertComponent($canvas, 'Text');
  }

  /**
   * Asserts that a component appears in a layout canvas.
   *
   * @param \Behat\Mink\Element\ElementInterface $canvas
   *   The layout canvas element.
   * @param string $label
   *   The component label.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The expected component.
   */
  protected function assertComponent(ElementInterface $canvas, string $label) : ElementInterface {
    $selector = sprintf('.coh-layout-canvas-list-item[data-type="%s"]', $label);
    return $this->waitForElementVisible('css', $selector, $canvas);
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
   * Waits for the element browser sidebar to be visible.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The element browser sidebar.
   */
  protected function waitForElementBrowser() : ElementInterface {
    return $this->waitForElementVisible('css', '.coh-element-browser-modal');
  }

  /**
   * Locates a button by its ARIA label and presses it.
   *
   * @param \Behat\Mink\Element\ElementInterface $container
   *   The element that contains the button.
   * @param string $button_label
   *   The button's ARIA label.
   */
  private function pressAriaButton(ElementInterface $container, string $button_label) : void {
    $selector = sprintf('button[aria-label="%s"]', $button_label);
    $button = $container->find('css', $selector);
    $this->assertInstanceOf(ElementInterface::class, $button);
    $button->press();
  }

  /**
   * Waits for an element to become visible on the page.
   *
   * @param string $selector
   *   The element selector, e.g. 'css', 'xpath', etc.
   * @param mixed $locator
   *   The element locator, such as a CSS selector or XPath query.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   (optional) The element which will contain the elements we are waiting
   *   for. Defaults to the page.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The element that has become visible.
   */
  protected function waitForElementVisible(string $selector, $locator, ElementInterface $container = NULL) : ElementInterface {
    $container = $container ?: $this->getSession()->getPage();

    $element = $container->waitFor(10, function (ElementInterface $container) use ($selector, $locator) {
      $element = $container->find($selector, $locator);
      return $element && $element->isVisible() ? $element : NULL;
    });

    $this->assertInstanceOf(ElementInterface::class, $element);
    return $element;
  }

}
