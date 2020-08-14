<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Tests the "Accordion container and Accordion item" components.
 *
 * @group acquia_cms
 */
class AccordionComponentTest extends CohesionTestBase {

  /**
   * Tests that the component can be added to a layout canvas.
   */
  public function testComponent() {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');

    // Add the component to the layout canvas.
    $canvas = $this->waitForElementVisible('css', '.coh-layout-canvas');
    $accordion_container = $this->addComponent($canvas, 'Accordion container');
    $accordion_item = $this->addComponentToDropZone($accordion_container, 'Accordion item');
    $this->addComponentToDropZone($accordion_item, 'Text');
  }

  /**
   * Tests that component can be edited by a specific user role.
   *
   * @param string $role
   *   The ID of the user role to test with.
   *
   * @dataProvider providerEditAccess
   */
  public function testEditAccess(string $role) {
    $account = $this->createUser();
    $account->addRole($role);
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/admin/cohesion/components/components');
    $this->editComponentDefinition('Interactive components', 'Accordion container');
    $this->getSession()->back();
    $this->editComponentDefinition('Interactive components', 'Accordion item');
  }

}
