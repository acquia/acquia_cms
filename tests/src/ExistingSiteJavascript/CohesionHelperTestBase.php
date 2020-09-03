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
   * @param array $components_label
   *   Array of component's label in helper.
   */
  protected function addHelper(ElementInterface $canvas, string $label, array $components_label) {
    $this->pressAriaButton($canvas, 'Add content');
    $this->selectHelperInElementBrowser($label);
    $this->assertAllComponentsOfHelper($canvas, $components_label);
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
   * Asserts that a helper appears in a layout canvas.
   *
   * @param \Behat\Mink\Element\ElementInterface $canvas
   *   The layout canvas element.
   * @param array $components_label
   *   Array of component's label.
   */
  protected function assertAllComponentsOfHelper(ElementInterface $canvas, array $components_label) {
    foreach ($components_label as $label) {
      $selector = sprintf('.coh-layout-canvas-list-item[data-type="%s"]', $label);
      $this->waitForElementVisible('css', $selector, $canvas);
    }
  }

  /**
   * Data provider for testing helpers in the layout canvas.
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerAddHelperToLayoutCanvas() {
    return [
      [
        ['content_author', 'site_builder'],
      ],
    ];
  }

}
