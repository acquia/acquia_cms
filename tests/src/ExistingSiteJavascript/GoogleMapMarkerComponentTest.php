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
    $component_added = $this->addComponent($canvas, 'Google map marker');
    $edit_form = $this->editComponent($component_added);

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

    // Visit to cohesion components page.
    $this->drupalGet('/admin/cohesion/components/components');
    $assert_session = $this->assertSession();

    // Ensure that the group containing the component is open.
    $details = $assert_session->elementExists('css', 'details > summary:contains(Map components)')->getParent();
    if (!$details->hasAttribute('open')) {
      $details->find('css', 'summary')->click();
    }

    $assert_session->elementExists('css', 'tr:contains("Google map marker")', $details)
      ->clickLink('Edit');
    $this->waitForElementVisible('css', '.cohesion-component-edit-form');
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
