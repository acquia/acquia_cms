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

}
