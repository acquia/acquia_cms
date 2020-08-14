<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Tests 'Card - Horizontal (16:9)' cohesion component.
 *
 * @group acquia_cms
 */
class Horizontal16Ratio9CardComponentTest extends CohesionTestBase {

  /**
   * Tests that the component can be added to a layout canvas.
   */
  public function testComponent() {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    // Create a random image that we can select in the media library when
    // editing the component.
    $this->createMedia(['bundle' => 'image']);

    $this->drupalGet('/node/add/page');

    // Add the component to the layout canvas.
    $canvas = $this->waitForElementVisible('css', '.coh-layout-canvas');
    $component = $this->addComponent($canvas, 'Card - Horizontal (16:9)');
    $edit_form = $this->editComponent($component);

    $this->openMediaLibrary($edit_form, 'Select image');
    $this->selectMedia(0);
    $this->insertSelectedMedia();

    $edit_form->fillField('Heading', 'Test Heading');
    $edit_form->fillField('Sub Heading', 'Test Sub-Heading');
    $edit_form->fillField('Paragraph', 'Test Paragraph');
    $edit_form->fillField('Link to page', 'https://www.acquia.com');
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
    $this->editComponentDefinition('General components', 'Card - Horizontal (16:9)');
  }

}
