<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Tests the "Two column page with hero helpers".
 *
 * @group acquia_cms
 */
class TwoColumnPageWithHeroHelperTest extends CohesionHelperTestBase {

  /**
   * Tests that the helper can be added to a layout canvas.
   *
   * @param string[] $roles
   *   Additional user roles to apply to the account being logged in.
   *
   * @dataProvider providerHelperInstallation
   */
  public function testHelper(array $roles = []) {
    $account = $this->createUser();
    array_walk($roles, [$account, 'addRole']);
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');

    // Add the helper to the layout canvas.
    $canvas = $this->waitForElementVisible('css', '.coh-layout-canvas');
    $this->addHelper($canvas, 'Two column page with hero', ['Hero', 'Breadcrumb', 'Left col', 'Right left']);
  }

  /**
   * Tests that helper can be edited by a specific user role.
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

    // Visit to cohesion helpers page.
    $this->drupalGet('/admin/cohesion/helpers/helpers');
    $this->editHelperDefinition('Layout helpers', 'Two column page with hero');
  }

}
