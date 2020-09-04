<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Behat\Mink\Element\ElementInterface;

/**
 * Base class for testing Acquia CMS's Cohesion Helpers.
 */
abstract class CohesionHelperTestBase extends CohesionTestBase {

  /**
   * {@inheritdoc}
   */
  protected function editDefinition(string $group, string $label) : ElementInterface {
    parent::editDefinition($group, $label);
    return $this->waitForElementVisible('css', '.cohesion-helper-edit-form', $this->getSession()->getPage());
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
