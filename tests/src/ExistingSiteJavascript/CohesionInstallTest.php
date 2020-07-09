<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Behat\Mink\Element\ElementInterface;
use PHPUnit\Framework\AssertionFailedError;
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
    $canvas = $assert_session->waitForElementVisible('css', '.coh-layout-canvas');
    $this->assertNotEmpty($canvas);

    $add_component_button = $canvas->find('css', 'button[aria-label="Add content"]');
    $this->assertNotEmpty($add_component_button);
    $add_component_button->press();

    $component = $assert_session->waitForElementVisible('css', '.coh-element-browser-modal .coh-layout-canvas-list-item[data-title="Text"]');
    $this->assertNotEmpty($component);
    $component->doubleClick();

    $component_added = $canvas->waitFor(10, function (ElementInterface $canvas) {
      $component = $canvas->find('css', '.coh-layout-canvas-list-item[data-type="Text"]');
      return $component && $component->isVisible() ? $component : FALSE;
    });
    $this->assertNotEmpty($component_added);

    $component_added->doubleClick();
    $edit_form = $assert_session->waitForElementVisible('css', '.coh-layout-canvas-settings');
    try {
      $this->assertNotEmpty($edit_form);
    }
    catch (AssertionFailedError $e) {
      $content = $this->getSession()->getPage()->getContent();
      $content = strip_tags($content);
      print_r($content);
      throw new AssertionFailedError($e->getMessage(), $e->getCode(), $e);
    }
  }

}
