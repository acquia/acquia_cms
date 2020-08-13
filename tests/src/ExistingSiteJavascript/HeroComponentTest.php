<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Tests Hero Component.
 *
 * @group acquia_cms
 */
class HeroComponentTest extends CohesionTestBase {

  /**
   * Test that the Hero component is installed.
   *
   * And used in Cohesion's layout canvas.
   */
  public function testComponentInstalled() {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');
    $page = $this->getSession()->getPage();
    $this->assertSession();

    // Create a random image that we can select in the media library when
    // editing the component.
    $this->createMedia(['bundle' => 'image']);

    // Content.
    $page->fillField('Title', 'Cohesion Hero component');

    // Add the cohesion component in the field layout canvas.
    $canvas = $this->waitForElementVisible('css', '.coh-layout-canvas');
    $component_added = $this->addComponent($canvas, 'Hero');
    $edit_form = $this->editComponent($component_added);

    // Test image field.
    $this->openMediaLibrary($edit_form, 'Select image');
    $this->selectMedia(0);
    $this->insertSelectedMedia();

    $edit_form->fillField('Link to page', 'https://www.acquia.com');

    // Layout.
    $edit_form->clickLink('Layout');

    // Check if all the height styles are there in the select list.
    $edit_form->selectFieldOption('Height', 'Large');
    $edit_form->selectFieldOption('Height', 'Small');
    $edit_form->selectFieldOption('Height', '60% height of viewport');

    // Check if all the text position styles are there in the select list.
    $edit_form->selectFieldOption('Text position', 'Left');
    $edit_form->selectFieldOption('Text position', 'Right');
    $edit_form->selectFieldOption('Text position', 'Center');

    // Check if all the padding styles are there in the select list.
    $edit_form->selectFieldOption('Padding top and bottom', 'None');
    $edit_form->selectFieldOption('Padding top and bottom', 'Top only');
    $edit_form->selectFieldOption('Padding top and bottom', 'Bottom only');
    $edit_form->selectFieldOption('Padding top and bottom', 'Top and bottom');

    $edit_form->selectFieldOption('Padding left and right', 'None');
    $edit_form->selectFieldOption('Padding left and right', 'Left and right');

    // Check if all the image position styles are there in the select list.
    $edit_form->selectFieldOption('Image position', 'Right to the content');
    $edit_form->selectFieldOption('Image position', 'Left to the content');

    // Styles.
    $edit_form->clickLink('Style');

    // Check if all the button styles are there in the select list.
    $edit_form->selectFieldOption('Button style', 'Button light');
    $edit_form->selectFieldOption('Button style', 'Button dark');
    $edit_form->selectFieldOption('Button style', 'Button default');
    $edit_form->selectFieldOption('Button style', 'None (transparent)');

    $edit_form->pressButton('Apply');
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
    $this->editComponentDefinition('Hero components', 'Hero');
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
