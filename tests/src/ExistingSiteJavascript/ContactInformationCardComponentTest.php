<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Tests 'Contact Information' cohesion component.
 *
 * @group acquia_cms
 * @group site_studio
 * @group low_risk
 * @group pr
 * @group push
 */
class ContactInformationCardComponentTest extends CohesionComponentTestBase {

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
    $edit_form = $this->getLayoutCanvas()->add('Contact information card')->edit();
    $this->openMediaLibrary($edit_form, 'Select image');
    $this->selectMediaSource("Media Types");
    $this->assertSession()->waitForElementVisible('css', '.media-library-content');
    $this->selectMedia(0);
    $this->insertSelectedMedia();
    $this->assertSession()->waitForElementVisible('css', '.ssa-modal-sidebar-editor');
    $this->assertSession()->waitForElementVisible('css', '.ssa-modal-sidebar-editor .sc-45mvqj-0');
    $this->assertSession()->waitForText('Card heading element');
    $edit_form->fillField('Card heading element', 'h3');
    $this->assertSession()->waitForText('This is the Heading');
    $edit_form->fillField('Card heading', 'Card heading');
    $this->assertSession()->waitForText('Contact name');
    $edit_form->fillField('Contact name', 'Leia Organa');
    $this->assertSession()->waitForText('Company');
    $edit_form->fillField('Company', 'Acquiaville');
    $this->assertSession()->waitForText('Address');
    $edit_form->fillField('Address', 'City Hall,200 main ST,Acquiaville');
    $this->assertSession()->waitForText('Telephone');
    $edit_form->fillField('Telephone', '9820964326');
    $this->assertSession()->waitForText('Email');
    $edit_form->fillField('Email', 'acquiaindia@test.com');
  }

  /**
   * Tests that component can be edited by a specific user role.
   *
   * @param string $role
   *   The ID of the user role to test with.
   *
   * @dataProvider providerEditAccess
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testEditAccess(string $role) {
    $account = $this->createUser();
    $account->addRole($role);
    $account->save();
    $this->drupalLogin($account);

    // Visit to cohesion components page.
    $this->drupalGet('/admin/cohesion/components/components');
    $this->editDefinition('Card components', 'Contact information card');
  }

}
