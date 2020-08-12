<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Test that "Google map marker" component is installed and operating correctly.
 *
 * @group acquia_cms
 */
class GoogleMapMarkerComponentTest extends CohesionTestBase {

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
    $google_map_marker = $this->addComponent($canvas, 'Google map marker');
    $edit_form = $this->editComponent($google_map_marker);

    $edit_form->fillField('Address', 'Test Address');
    $edit_form->fillField('Latitude', '22.52138');
    $edit_form->fillField('Longitude', '88.294324');
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
    $this->editComponentDefinition('Map components', 'Google map marker');
  }

  /**
   * Data provider for ::testEditAccess().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerEditAccess() {
    return [
      ['site_builder'],
      ['developer'],
    ];
  }

}
