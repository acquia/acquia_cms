<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_common
 * @group acquia_cms
 * @group risky
 */
class BasicPermissionsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_common',
    'media',
    'toolbar',
    'views',
  ];

  /**
   * Disable strict config schema checks in this test.
   *
   * Cohesion has a lot of config schema errors, and until they are all fixed,
   * this test cannot pass unless we disable strict config schema checking
   * altogether. Since strict config schema isn't critically important in
   * testing this functionality, it's okay to disable it for now, but it should
   * be re-enabled (i.e., this property should be removed) as soon as possible.
   *
   * @var bool
   */
  // @codingStandardsIgnoreStart
  protected $strictConfigSchema = FALSE;
  // @codingStandardsIgnoreEnd

  /**
   * Tests the role which shoul exists.
   *
   * @param string $role
   *   A role name.
   *
   * @dataProvider providerRoleExist
   */
  public function testRoleExists(string $role) {
    $this->assertInstanceOf(Role::class, Role::load($role));
  }

  /**
   * Tests the role which shouldn't exists.
   *
   * @param string $role
   *   A role name.
   *
   * @dataProvider providerRoleNotExists
   */
  public function testRoleNotExists(string $role) {
    $this->assertNotInstanceOf(Role::class, Role::load($role));
  }

  /**
   * Tests basic capabilities of our user roles.
   *
   * - Content authors, editors, and administrators should all be able to access
   *   the toolbar and the content overview.
   * - User administrator should be able to access the toolbar and the user
   *   overview.
   *
   * @dataProvider providerBasicPermissions
   */
  public function testBasicPermissions(string $role, array $permissions, array $no_permissions = []) {
    $this->assertPermissions($role, $permissions);
    if ($no_permissions) {
      $this->assertNoPermissions($role, $no_permissions);
    }
  }

  /**
   * Tests the basic module permissions.
   *
   * @param mixed $modules
   *   A module or an array of modules.
   * @param array $roles
   *   An array of roles.
   *
   * @dataProvider providerModulePermissions
   */
  public function testModulePermissions($modules, array $roles) {
    $modules = is_string($modules) ? [$modules] : $modules;
    \Drupal::service('module_installer')->install($modules);
    foreach ($roles as $role => $permissions) {
      $this->testBasicPermissions($role, $permissions);
    }
  }

  /**
   * Defines an array of role which should exists.
   */
  public function providerRoleExist(): array {
    return [
      [
        "administrator",
        "site_builder",
        "user_administrator",
      ],
    ];
  }

  /**
   * Defines an array of role which shouldn't exists.
   */
  public function providerRoleNotExists(): array {
    return [
      [
        "content_administrator",
        "content_author",
        "content_editor",
      ],
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
  private function assertPermissions(string $role, array $permissions) : void {
    $role = Role::load($role);
    $missing_permissions = array_diff($permissions, $role->getPermissions());
    $this->assertEmpty($missing_permissions);
  }

  /**
   * Asserts that a role does not have a set of permissions.
   *
   * @param string $role
   *   The ID of the role to check.
   * @param string[] $permissions
   *   An array of permissions the role is not expected to have.
   */
  private function assertNoPermissions(string $role, array $permissions) : void {
    $role = Role::load($role);
    $granted_permissions = array_intersect($role->getPermissions(), $permissions);
    $this->assertEmpty($granted_permissions);
  }

  /**
   * Defines basic permissions & no permissions for roles.
   */
  public function providerBasicPermissions(): array {
    return [
      [
        'site_builder',
        [
          'use text format filtered_html',
          'use text format full_html',
          'view the administration theme',
        ],
        ['administer shield'],
      ],
      [
        'user_administrator',
        [
          'administer CAPTCHA settings',
          'administer honeypot',
          'administer recaptcha',
          'administer seckit',
          'administer site configuration',
          'administer users',
          'manage password reset',
          'view the administration theme',
        ],
        ['administer shield'],
      ],
    ];
  }

  /**
   * Defines an array of modules & permissions to roles.
   */
  public function providerModulePermissions(): array {
    return [
        [
          'shield',
          [
            'user_administrator' => ['administer shield'],
          ],
        ],
    ];
  }

}
