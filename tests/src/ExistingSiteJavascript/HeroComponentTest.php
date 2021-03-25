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

    // Add the component to the layout canvas.
    $edit_form = $this->getLayoutCanvas()->add('Hero')->edit();
    $this->waitForElementVisible('css', 'coh-typeahead input.form-control ', $this->getSession()->getPage());
    $edit_form->fillField('Link to page or URL', 'https://www.acquia.com');
    $edit_form->fillField('Button text', 'Button Text');
    $edit_form->selectFieldOption('Target', 'New window');
    $edit_form->selectFieldOption('Button style', 'Link button color');
    $edit_form->selectFieldOption('Show breadcrumbs', 'Show breadcrumbs on solid light background');

    $assert_styles = function (string $select, array $styles) use ($assert_session, $edit_form) {
      foreach ($styles as $style) {
        $assert_session->optionExists($select, $style, $edit_form);
      }
    };

    $edit_form->clickLink('Layout and style');
    // Check if all the height styles are there in the select list.
    $assert_styles('Hero height', [
      'Fluid (Scales to fit browser height)',
      'Tall',
      'Short',
    ]);

    // Check if all the layout options are there in the select list.
    $assert_styles('Text and drop zone layout', [
      'Text left - Drop zone right',
      'Text left - Drop zone right',
      'Text right - Drop zone left',
    ]);

    // Check if all the heading text colors are there in the select list.
    $assert_styles('Heading text color', [
      'Light',
      'Dark',
      'Colored',
    ]);

    // Check if all the text colors are there in the select list.
    $assert_styles('Text color', [
      'Light',
      'Dark',
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
