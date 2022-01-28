<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Verify that Cohesion Drupal block component has the following options:.
 *
 * - Article Cards
 * - Language Switcher
 * - Past Events
 * - Social Media Links
 * - Upcoming Events
 * - User Login.
 *
 * @group acquia_cms
 * @group site_studio
 * @group low_risk
 * @group pr
 * @group push
 */
class DrupalBlockComponentTest extends CohesionComponentTestBase {

  /**
   * Tests that the helper can be added to a layout canvas.
   *
   * @param string[] $roles
   *   Additional user roles to apply to the account being logged in.
   *
   * @dataProvider providerAddComponentToLayoutCanvas
   */
  public function testComponent(array $roles = []) {
    $account = $this->createUser();
    array_walk($roles, [$account, 'addRole']);
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');
    $assert_session = $this->assertSession();
    // Add the component to the layout canvas & edit it.
    $edit_form = $this->getLayoutCanvas()->add('Drupal blocks')->edit();
    $assert_session->waitForElementVisible('css', '.coh-select .form-control');

    // Assert that select block exits.
    $edit_form->hasSelect('Select block');

    // Assert that following blocks are available as option.
    $assert_session->optionExists('Select block', 'Article Cards', $edit_form);
    $assert_session->optionExists('Select block', 'Language switcher', $edit_form);
    $assert_session->optionExists('Select block', 'Past Events', $edit_form);
    $assert_session->optionExists('Select block', 'Social Media Links', $edit_form);
    $assert_session->optionExists('Select block', 'Upcoming Events', $edit_form);
    $assert_session->optionExists('Select block', 'User login', $edit_form);
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
    $this->editDefinition('Dynamic components', 'Drupal blocks');
  }

}
