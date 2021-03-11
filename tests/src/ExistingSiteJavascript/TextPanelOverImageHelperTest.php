<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Tests the "Image Background Hero" helper.
 *
 * @group acquia_cms
 * @group site_studio
 * @group low_risk
 * @group pr
 * @group push
 */
class TextPanelOverImageHelperTest extends CohesionHelperTestBase {

  /**
   * Tests that the helper can be added to a layout canvas.
   *
   * @param string[] $roles
   *   Additional user roles to apply to the account being logged in.
   *
   * @dataProvider providerAddHelperToLayoutCanvas
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testHelper(array $roles = []) {
    $account = $this->createUser();
    array_walk($roles, [$account, 'addRole']);
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');

    // Add the helper to the layout canvas.
    $this->getLayoutCanvas()->addHelper('Text panel over image section', [
      'Text panel over image - 1 column layout',
    ]);
  }

  /**
   * Tests that helper can be edited by a specific user role.
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

    $this->drupalGet('/admin/cohesion/helpers/helpers');
    $this->editDefinition('Text Sections', 'Text panel over image section');
  }

}
