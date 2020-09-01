<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Behat\Mink\Element\ElementInterface;

/**
 * Base class for testing Acquia CMS's Cohesion Helpers.
 */
abstract class CohesionHelperTestBase extends CohesionTestBase {

  /**
   * Tries to open the edit form for a helper in the administrative UI.
   *
   * @param string $group
   *   The group to which the component belongs.
   * @param string $label
   *   The label of the component.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The component's administrative edit form.
   */
  protected function editHelperDefinition(string $group, string $label) : ElementInterface {
    $assert_session = $this->assertSession();

    // Ensure that the helper's group container is open.
    $group = $assert_session->elementExists('css', "details > summary:contains($group)");
    if ($group->getParent()->hasAttribute('open') === FALSE) {
      $group->click();
    }

    $assert_session->elementExists('css', 'tr:contains("' . $label . '")', $group->getParent())
      ->clickLink('Edit');

    return $this->waitForElementVisible('css', '.cohesion-helper-edit-form');
  }

  /**
   * Adds a helper to a layout canvas.
   *
   * @param \Behat\Mink\Element\ElementInterface $canvas
   *   The layout canvas element.
   * @param string $label
   *   The helper label.
   * @param string $layout_canvas_label
   *   The helper label which is used by layout canvas.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The helper that has been added to the layout canvas.
   */
  protected function addHelper(ElementInterface $canvas, string $label, string $layout_canvas_label) : ElementInterface {
    $this->pressAriaButton($canvas, 'Add content');
    $this->selectHelperInElementBrowser($label);
    return $this->assertComponent($canvas, $layout_canvas_label);
  }

  /**
   * Selects a helper from the element browser.
   *
   * @param string $label
   *   The label of the helper to select.
   */
  private function selectHelperInElementBrowser(string $label) : void {
    $element_browser = $this->waitForElementBrowser();
    $this->waitForElementVisible('css', '.coh-layout-canvas-menu');
    $this->assertSession()->elementExists('css', '.coh-nav-dropdown')->click();
    $this->waitForElementVisible('css', "a.nav-link:contains('Helpers')")->press();
    $selector = sprintf('.coh-layout-canvas-list-item[data-title="%s"]', $label);
    $this->waitForElementVisible('css', $selector, $element_browser)->doubleClick();
    $this->pressAriaButton($element_browser, 'Close sidebar browser');
  }

  /**
   * Data provider for testing helpers in the layout canvas.
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerHelperInstallation() {
    return [
      [
        ['content_author', 'site_builder'],
      ],
    ];
  }

}
