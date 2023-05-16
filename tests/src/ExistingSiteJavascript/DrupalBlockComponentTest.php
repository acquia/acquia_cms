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
  public function testComponent(array $roles = []): void {
    $account = $this->createUser();
    array_walk($roles, [$account, 'addRole']);
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();
    // Add the component to the layout canvas & edit it.
    /** @var \Behat\Mink\Element\TraversableElement $edit_form */
    $edit_form = $this->getLayoutCanvas()->add('Drupal blocks')->edit();
    $assert_session->waitForElementVisible('css', '.coh-select .form-control');

    // Assert that select block exits.
    $edit_form->hasSelect('Select block');

    // Assert that following blocks are available as option.
    $assert_session->optionExists('Select block', 'views_block__article_cards_recent_articles_block', $edit_form);
    $assert_session->optionExists('Select block', 'language_switcher', $edit_form);
    $assert_session->optionExists('Select block', 'views_block__event_cards_past_events_block', $edit_form);
    $assert_session->optionExists('Select block', 'views_block__event_cards_upcoming_events_block', $edit_form);
    $assert_session->optionExists('Select block', 'user_login', $edit_form);
  }

  /**
   * Tests that component can be edited by a specific user role.
   *
   * @param string $role
   *   The ID of the user role to test with.
   *
   * @dataProvider providerEditAccess
   */
  public function testEditAccess(string $role): void {
    $account = $this->createUser();
    $account->addRole($role);
    $account->save();
    $this->drupalLogin($account);

    // Visit to cohesion components page.
    $this->drupalGet('/admin/cohesion/components/components');
    $this->editDefinition('Dynamic components', 'Drupal blocks');
  }

  /**
   * We are overriding this method due to JS issue.
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerAddComponentToLayoutCanvas(): array {
    // @todo Find a solution and remove this function from here.
    return [
      [
        ['administrator', 'site_builder'],
      ],
    ];
  }

}
