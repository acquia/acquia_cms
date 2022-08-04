<?php

namespace Drupal\Tests\acquia_cms_toolbar\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_toolbar
 * @group acquia_cms
 * @group risky
 */
class ToolbarPermissionsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_toolbar',
    'toolbar',
  ];

  /**
   * Tests basic capabilities of our user roles.
   *
   * - Content authors, editors, and administrators should all be able to access
   *   the toolbar and the content overview.
   * - User administrator should be able to access the toolbar and the user
   *   overview.
   *
   * @param string $role
   *   The ID of the user role to test with.
   *
   * @dataProvider providerRoles
   */
  public function testBasicPermissions(string $role) {
    $account = $this->createUser();
    $account->addRole($role);
    $account->save();
    $this->drupalLogin($account);

    $assert_session = $this->assertSession();
    $this->assertPermissions($role, ['access toolbar']);
    if ($role === 'user_administrator') {
      $this->drupalGet('/admin/people');
      $assert_session->statusCodeEquals(200);
    }
    elseif ($role === 'developer' || $role === 'site_builder') {
      $assert_session->statusCodeEquals(200);
    }
    else {
      $this->drupalGet('/admin/content');
      $this->drupalGet('/admin/content/media');
      $assert_session->statusCodeEquals(200);
    }
  }

  /**
   * Data provider for ::testBasicPermissions().
   *
   * @return array
   *   Sets of arguments to pass to the test method.
   */
  public function providerRoles() {
    return [
      ['content_administrator'],
      ['content_author'],
      ['content_editor'],
      ['developer'],
      ['site_builder'],
      ['user_administrator'],
    ];
  }

  /**
   * Asserts that a role has a set of permissions.
   *
   * @param string $role
   *   The ID of the role to check.
   * @param string[] $permissions
   *   An array of permissions the role is expected to have.
   */
  private function assertPermissions(string $role, array $permissions): void {
    $role = Role::load($role);
    $missing_permissions = array_diff($permissions, $role->getPermissions());
    $this->assertEmpty($missing_permissions);
  }

}
