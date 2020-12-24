<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Tests 'Contact Information' cohesion component.
 *
 * @group acquia_cms
 */
class ContactInformationComponentTest extends CohesionComponentTestBase {

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
    $edit_form = $this->getLayoutCanvas()->add('Contact information')->edit();
    $this->openMediaLibrary($edit_form, 'Select image');
    $this->selectMedia(0);
    $this->insertSelectedMedia();

    $edit_form->fillField('Title', 'Acquiaville');
    $edit_form->fillField('Address', 'City Hall,200 main ST,Acquiaville');
    $edit_form->fillField('Phone', '9820964326');
    $edit_form->fillField('Email', 'acquiaindia@test.com');
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
    $this->editDefinition('Basic components', 'Contact information');
  }

}
