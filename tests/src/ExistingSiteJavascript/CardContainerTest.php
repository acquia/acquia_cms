<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Test that "Card container" component is installed and operating correctly.
 *
 * @group acquia_cms
 * @group site_studio
 */
class CardContainerTest extends CohesionComponentTestBase {

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
    $edit_form = $this->getLayoutCanvas()->add('Card container')->edit();
    $this->assertSession()->optionExists('Inner gutters', 'Bleed into gutters', $edit_form);
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
    $this->editDefinition('Layout components', 'Card container');
  }

}
