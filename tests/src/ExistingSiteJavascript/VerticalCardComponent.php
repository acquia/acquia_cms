<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Tests 'Card - Vertical' cohesion component.
 *
 * @group acquia_cms
 * @group site_studio
 * @group low_risk
 * @group pr
 * @group push
 */
class VerticalCardComponent extends CohesionComponentTestBase {

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
    $edit_form = $this->getLayoutCanvas()->add('Card - Vertical')->edit();
    $this->openMediaLibrary($edit_form, 'Select image');
    $this->selectMedia(0);
    $this->insertSelectedMedia();

    $edit_form->fillField('Heading', 'Test Heading');
    $edit_form->fillField('Paragraph', 'Test Paragraph');
    $edit_form->fillField('Link text', 'Test link text');
    $edit_form->fillField('Link to page', 'https://www.acquia.com');
    $edit_form->fillField('Link title', 'Test link title');

    // Switch to style tab to check the existance of proper styles.
    $edit_form->clickLink('Style');

    $styles = [
      'Left',
      'Center',
      'Right',
    ];
    $assert_session = $this->assertSession();
    foreach ($styles as $style) {
      $assert_session->optionExists('Image alignment', $style, $edit_form);
      $assert_session->optionExists('Text Align', $style, $edit_form);
    }
    // Assert that Image Style options exists.
    $assert_session->optionExists('Image Style', 'Square');
    $assert_session->optionExists('Image Style', 'Circular');
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
    $this->editDefinition('Card components', 'Card - Vertical');
  }

}
