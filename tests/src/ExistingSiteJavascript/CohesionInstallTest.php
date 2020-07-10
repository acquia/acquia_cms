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
    $assert_session = $this->assertSession();

    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');
    $canvas = $this->waitForElementVisible('css', '.coh-layout-canvas');

    $add_component_button = $canvas->find('css', 'button[aria-label="Add content"]');
    $this->assertNotEmpty($add_component_button);
    $add_component_button->press();

    $this->waitForElementVisible('css', '.coh-element-browser-modal .coh-layout-canvas-list-item[data-title="Text"]')->doubleClick();

    $component_added = $canvas->waitFor(10, function (ElementInterface $canvas) {
      $component = $canvas->find('css', '.coh-layout-canvas-list-item[data-type="Text"]');
      return $component && $component->isVisible() ? $component : FALSE;
    });
    $this->assertNotEmpty($component_added);

    $assert_session->elementExists('css', '.coh-element-browser-modal button[aria-label="Close sidebar browser"]')->press();

    $assert_session->elementExists('css', 'button[aria-label="More actions"]', $component_added)->press();
    $this->waitForElementVisible('css', '.coh-layout-canvas-utils-dropdown-menu .coh-edit-btn')->press();

    $edit_form = $this->waitForElementVisible('css', '.coh-layout-canvas-settings');
    $edit_form->fillField('Component title', 'Example component');
    $edit_form->pressButton('Apply');

    $component_changed = $canvas->waitFor(10, function (ElementInterface $canvas) {
      $component = $canvas->find('css', '.coh-layout-canvas-list-item[data-type="Example component"]');
      return $component && $component->isVisible() ? $component : FALSE;
    });
    $this->assertNotEmpty($component_changed);
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
