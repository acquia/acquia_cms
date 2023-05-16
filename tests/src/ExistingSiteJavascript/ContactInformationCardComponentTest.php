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
  public function testComponent(): void {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);
    // Create a random image that we can select in the media library when
    // editing the component.
    $this->createMedia(['bundle' => 'image']);

    $this->drupalGet('/node/add/page');
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    // Add the component to the layout canvas.
    /** @var \Behat\Mink\Element\TraversableElement $edit_form */
    $edit_form = $this->getLayoutCanvas()->add('Contact information card')->edit();
    $this->openMediaLibrary($edit_form, 'Select image');
    $this->selectMediaSource("Media Types");
    $assertSession->waitForElementVisible('css', '.media-library-content');
    $this->selectMedia(0);
    $this->insertSelectedMedia();
    $assertSession->waitForElementVisible('css', '.ssa-modal-sidebar-editor');
    $assertSession->waitForElementVisible('css', '.ssa-modal-sidebar-editor .sc-45mvqj-0');
    $assertSession->waitForText('Card heading element');
    $edit_form->fillField('Card heading element', 'h3');
    $assertSession->waitForText('This is the Heading');
    $edit_form->fillField('Card heading', 'Card heading');
    $assertSession->waitForText('Contact name');
    $edit_form->fillField('Contact name', 'Leia Organa');
    $assertSession->waitForText('Company');
    $edit_form->fillField('Company', 'Acquiaville');
    $assertSession->waitForText('Address');
    $edit_form->fillField('Address', 'City Hall,200 main ST,Acquiaville');
    $assertSession->waitForText('Telephone');
    $edit_form->fillField('Telephone', '9820964326');
    $assertSession->waitForText('Email');
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
  public function testEditAccess(string $role): void {
    $account = $this->createUser();
    $account->addRole($role);
    $account->save();
    $this->drupalLogin($account);

    // Visit to cohesion components page.
    $this->drupalGet('/admin/cohesion/components/components');
    $this->editDefinition('Card components', 'Contact information card');
  }

}
