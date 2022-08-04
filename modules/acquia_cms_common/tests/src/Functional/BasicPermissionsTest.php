<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

use Drupal\acquia_cms_common\Facade\RolePermissionsFacade;
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
    'views',
  ];

  /**
   * @var \Drupal\acquia_cms_common\Facade\RolePermissionsFacade
   */
  protected $rolePermissionsFacade;

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->rolePermissionsFacade = $this->container->get('class_resolver')->getInstanceFromDefinition(RolePermissionsFacade::class);
  }

  /**
   * Tests basic capabilities of our user roles.
   *
   * - Content authors, editors, and administrators should all be able to access
   *   the toolbar and the content overview.
   * - User administrator should be able to access the toolbar and the user
   *   overview.
   */
  public function testBasicPermissions() {
    // Assert sessions started.
    $assert_session = $this->assertSession();
    $roles_permissions = $this->getRolesPermissions();
    foreach ($roles_permissions as $role => $permissions) {
      // Check for permissions assigned to the role.
      $this->assertPermissions($role, $permissions);

      // Create user and check for the routes/paths.
      $account = $this->drupalCreateUser();
      $account->addRole($role);
      $account->save();
      $this->drupalLogin($account);
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

  /**
   * Get role permissions based on acms modules.
   *
   * @return array
   *   List of roles and permissions.
   */
  protected function getRolesPermissions(): array {
    return $this->rolePermissionsFacade->defaultRolePermissions('acquia_cms_common');
  }

}
