<?php

namespace Drupal\Tests\acquia_cms_page\ExistingSiteJavascript;

use Drupal\Tests\acquia_cms\FunctionalJavascript\LayoutCanvas;
use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase;

/**
 * Does basic tests of the Page content type's layout canvas (Cohesion).
 *
 * @group acquia_cms_page
 * @group acquia_cms
 */
class LayoutCanvasTest extends ExistingSiteSelenium2DriverTestBase {

  /**
   * Tests the layout canvas on the Page content type.
   */
  public function testLayoutCanvas() {
    // @todo Once we have properly integrated our roles with Cohesion's dynamic
    // permissions, only use a role here.
    $account = $this->createUser([
      'access cpt_cat_general_components cohesion_component_category group',
    ]);
    $account->addRole('content_author');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');
    $layout_canvas = $this->assertLayoutCanvas('css', '#field_layout_canvas_0');
    $component = $layout_canvas->addComponent('Hero - center aligned text with background image');
    $edit_form = $layout_canvas->editComponent($component);
    $edit_form->fillField('title', 'Example component');
    $edit_form->pressButton('Apply');
    $layout_canvas->assertComponent('Example component');
  }

  /**
   * Asserts the presence of a layout canvas.
   *
   * @param string $selector
   *   The selector for the layout canvas element, e.g. 'css', 'xpath', etc.
   * @param mixed $locator
   *   The layout canvas element locator, e.g. a CSS selector, XPath query, etc.
   *
   * @return \Drupal\Tests\acquia_cms\FunctionalJavascript\LayoutCanvas
   *   The layout canvas element.
   */
  private function assertLayoutCanvas($selector, $locator) {
    $element = $this->assertSession()->elementExists($selector, $locator);
    return new LayoutCanvas($element->getXpath(), $this->getSession());
  }

}
