<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Tests the "Tab container horizontal" and "Tab item" components.
 *
 * @group acquia_cms
 */
class TabsComponentTest extends CohesionComponentTestBase {

  /**
   * Test that the components can be added to a layout canvas.
   */
  public function testComponent() {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');

    // Add the component to the layout canvas.
    $canvas = $this->waitForElementVisible('css', '.coh-layout-canvas');
    $tabs_container = $this->addComponent($canvas, 'Tabs container - horizontal tabs');
    $tab_item = $this->addComponentToDropZone($tabs_container, 'Tab item');
    $this->addComponentToDropZone($tab_item, 'Text and image');
  }

  /**
   * Tests that the components can be edited by a specific user role.
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

    // Visit to cohesion components page.
    $this->drupalGet('/admin/cohesion/components/components');
    $this->editComponentDefinition('Interactive components', 'Tabs container - horizontal tabs');
    $this->getSession()->back();
    $this->editComponentDefinition('Interactive components', 'Tab item');
  }

}
