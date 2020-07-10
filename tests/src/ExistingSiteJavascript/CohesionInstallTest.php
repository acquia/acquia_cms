<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Behat\Mink\Element\ElementInterface;
use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase;

/**
 * Tests that Cohesion is installed and operating correctly.
 *
 * @group acquia_cms
 */
class CohesionInstallTest extends ExistingSiteSelenium2DriverTestBase {

  /**
   * Tests that Cohesion's layout canvas can be used.
   */
  public function testLayoutCanvas() {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');
    $canvas = $this->waitForElementVisible('css', '.coh-layout-canvas');

    $this->pressAriaButton($canvas, 'Add content');
    $element_browser = $this->waitForElementBrowser();

    $component = $element_browser->waitFor(10, function (ElementInterface $element_browser) {
      return $element_browser->find('css', '.coh-layout-canvas-list-item[data-title="Text"]') ?: FALSE;
    });
    $this->assertInstanceOf(ElementInterface::class, $component);
    $component->doubleClick();

    $component_added = $canvas->waitFor(10, function (ElementInterface $canvas) {
      $component = $canvas->find('css', '.coh-layout-canvas-list-item[data-type="Text"]');
      return $component && $component->isVisible() ? $component : FALSE;
    });
    $this->assertNotEmpty($component_added);

    $this->pressAriaButton($element_browser, 'Close sidebar browser');

    $edit_form = $this->editComponent($component_added);
    $edit_form->fillField('Component title', 'Example component');
    $edit_form->pressButton('Apply');

    $component_changed = $canvas->waitFor(10, function (ElementInterface $canvas) {
      $component = $canvas->find('css', '.coh-layout-canvas-list-item[data-type="Example component"]');
      return $component && $component->isVisible() ? $component : FALSE;
    });
    $this->assertNotEmpty($component_changed);
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
    $settings = $form->waitFor(10, function (ElementInterface $form) {
      return $form->find('css', 'coh-component-form');
    });
    $this->assertInstanceOf(ElementInterface::class, $settings);

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
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The element that has become visible.
   */
  private function waitForElementVisible(string $selector, $locator) : ElementInterface {
    $element = $this->assertSession()->waitForElementVisible($selector, $locator);
    $this->assertInstanceOf(ElementInterface::class, $element);
    return $element;
  }

}
