<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Tests the "Button" components.
 *
 * @group acquia_cms
 * @group site_studio
 * @group low_risk
 * @group pr
 * @group push
 */
class ButtonComponentTest extends CohesionComponentTestBase {

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
    $edit_form = $this->getLayoutCanvas()->add('Button(s)')->edit();

    $edit_form->clickLink('Layout and style');
    // Check if all the button styles are there in the select list.
    $styles = [
      'Left',
      'Center',
      'Right',
    ];
    $assert_session = $this->assertSession();
    foreach ($styles as $style) {
      $assert_session->optionExists('Align buttons', $style, $edit_form);
    }
    $assert_session->optionExists('Add space below', 'Add space below', $edit_form);
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
    $this->editDefinition('Basic components', 'Button(s)');
  }

}
