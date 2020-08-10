<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Tests the "Button" components.
 *
 * @group acquia_cms
 */
class ButtonComponentTest extends CohesionTestBase {

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
    $button = $this->addComponent($canvas, 'Button');
    $edit_form = $this->editComponent($button);

    $edit_form->clickLink('Style');

    // Check if all the button styles are there in the select list.
    $edit_form->selectFieldOption('Button Style', 'Button CTA');
    $edit_form->selectFieldOption('Button Style', 'Button Solid');
    $edit_form->selectFieldOption('Button Style', 'Button Outline');
    $edit_form->selectFieldOption('Button Style', 'Button Unstyled');
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
    $this->editComponentDefinition('General components', 'Button');
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
