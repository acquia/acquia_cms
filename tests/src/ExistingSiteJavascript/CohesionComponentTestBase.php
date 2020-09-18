<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Behat\Mink\Element\ElementInterface;

/**
 * Base class for testing Acquia CMS's Cohesion Components.
 */
abstract class CohesionComponentTestBase extends CohesionTestBase {

  /**
   * {@inheritdoc}
   */
  protected function editDefinition(string $group, string $label) : ElementInterface {
    parent::editDefinition($group, $label);
    return $this->waitForElementVisible('css', '.cohesion-component-edit-form', $this->getSession()->getPage());
  }

  /**
   * Data provider for testing components in the layout canvas.
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerAddComponentToLayoutCanvas() {
    return [
      [
        ['content_author', 'site_builder'],
      ],
    ];
  }

}
