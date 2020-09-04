<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Tests the "Button" components.
 *
 * @group acquia_cms
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
    $edit_form = $this->getLayoutCanvas()->add('Button')->edit();

    $edit_form->clickLink('Style');
    // Check if all the button styles are there in the select list.
    $styles = [
      'Button CTA',
      'Button Solid',
      'Button Outline',
      'Button Unstyled',
    ];
    $assert_session = $this->assertSession();
    foreach ($styles as $style) {
      $assert_session->optionExists('Button Style', $style, $edit_form);
    }
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
    $this->editDefinition('General components', 'Button');
  }

}
