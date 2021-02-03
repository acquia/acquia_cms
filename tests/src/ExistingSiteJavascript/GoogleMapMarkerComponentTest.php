<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Test that "Google map marker" component is installed and operating correctly.
 *
 * @group acquia_cms
 * @group site_studio
 * @group low_risk
 * @group pr
 * @group push
 */
class GoogleMapMarkerComponentTest extends CohesionComponentTestBase {

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
    $edit_form = $this->getLayoutCanvas()->add('Google map marker')->edit();

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
    $this->editDefinition('Map components', 'Google map marker');
  }

}
