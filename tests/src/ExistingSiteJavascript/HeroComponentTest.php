<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Tests the Hero component.
 *
 * @group acquia_cms
 * @group site_studio
 * @group low_risk
 * @group pr
 * @group push
 */
class HeroComponentTest extends CohesionComponentTestBase {

  /**
   * Test that the Hero component can be added to a layout canvas.
   */
  public function testComponentInstalled() {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');
    $assert_session = $this->assertSession();

    // Create a random image that we can select in the media library when
    // editing the component.
    $this->createMedia(['bundle' => 'image']);

    // Add the component to the layout canvas.
    $edit_form = $this->getLayoutCanvas()->add('Hero')->edit();

    // Test adding an image to the component.
    $edit_form->selectFieldOption('Image/Video', 'Image');
    $this->openMediaLibrary($edit_form, 'Select image');
    $this->selectMedia(0);
    $this->insertSelectedMedia();

    $edit_form->fillField('Link to page', 'https://www.acquia.com');

    $assert_styles = function (string $select, array $styles) use ($assert_session, $edit_form) {
      foreach ($styles as $style) {
        $assert_session->optionExists($select, $style, $edit_form);
      }
    };

    $edit_form->clickLink('Layout and style');
    // Check if all the height styles are there in the select list.
    $assert_styles('Height', [
      'Large',
      'Small',
      '60% height of viewport',
    ]);

    // Check if all the text position styles are there in the select list.
    $assert_styles('Text position', [
      'Left',
      'Right',
      'Center',
    ]);

    // Check if all the padding styles are there in the select list.
    $assert_styles('Padding top and bottom', [
      'None',
      'Top - small',
      'Bottom - small',
      'Top and bottom - small',
      'Top - medium',
      'Bottom - medium',
      'Top and bottom - medium',
      'Top - large',
      'Bottom - large',
      'Top and bottom - large',
    ]);
    $assert_styles('Padding left and right', [
      'None',
      'Left - small',
      'Right - small',
      'Left and right - small',
      'Left - medium',
      'Right - medium',
      'Left and right - medium',
      'Left - large',
      'Right - large',
      'Left and right - large',
    ]);

    // Check if all the image position styles are there in the select list.
    $assert_styles('Position', [
      'Right to the content',
      'Left to the content',
    ]);

    // Check if all the text box position styles are there in the select list.
    $assert_styles('Text box position', [
      'Left',
      'Right',
      'Center',
    ]);

    $edit_form->clickLink('Style');

    // Check if all the button styles are there in the select list.
    $assert_styles('Button style', [
      'Button light',
      'Button dark',
      'Button default',
      'None (transparent)',
    ]);
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
    $this->editDefinition('Feature sections', 'Hero');
  }

}
