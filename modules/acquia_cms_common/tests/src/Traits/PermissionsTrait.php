<?php

namespace Drupal\Tests\acquia_cms_common\Traits;

use Drupal\user\Entity\Role;
use Symfony\Component\Yaml\Yaml;

/**
 * Trait includes permissions matrix for Site Studio.
 */
trait PermissionsTrait {

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
    $this->assertEmpty($missing_permissions, print_r($missing_permissions, TRUE));
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
   * Gets the basePath for permission fixtures.
   *
   * @return string
   *   Returns basePath for fixtures.
   */
  abstract public function getFixtureBasePath(): string;

  /**
   * @throws \Exception
   */
  protected function getPermissionsByRole(string $role): array {
    $roleConfig = $this->getFixtureBasePath() . "/user.role.$role.yml";
    $permissions = [];
    if (file_exists($roleConfig)) {
      $user_role = Yaml::parse(file_get_contents($roleConfig));
      $permissions = $user_role['permissions'] ?? [];
    }
    if (!$permissions) {
      throw new \Exception("Permissions yaml file not exist for role: `${role}`.");
    }
    return $permissions;
  }

  /**
   * Tests the role which should exists.
   *
   * @param array $roles
   *   An array of roles which should exist.
   * @param array $roles_not_exist
   *   An array of roles which shouldn't exist.
   *
   * @dataProvider providerRoleExistNotExist
   */
  public function testRoleExistNotExist(array $roles, array $roles_not_exist = []) {
    foreach ($roles as $role) {
      $this->assertInstanceOf(Role::class, Role::load($role), "Role `${role}` should exist.");
    }
    foreach ($roles_not_exist as $role) {
      $this->assertNotInstanceOf(Role::class, Role::load($role), "Role `${role}` should not exist.");
    }
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
    $contrib_module_permissions = $this->contribModulePermissions($role);
    if (!empty($contrib_module_permissions)) {
      $permissions = array_merge($permissions, $contrib_module_permissions);
    }
    $this->assertPermissions($role, $permissions);
    if ($no_permissions) {
      $this->assertNoPermissions($role, $no_permissions);
    }
  }

  /**
   * Assign permissions only if the below modules are enabled.
   *
   * @param string $role
   *   User role.
   *
   * @return array
   *   Returns list of permissions.
   */
  public function contribModulePermissions($role): ?array {
    $module_permissions = [];
    if ($role === 'user_administrator') {
      $module_handler = $this->container->get('module_handler');
      $permissions = [
        'shield' => 'administer shield',
        'honeypot' => 'administer honeypot',
        'captcha' => 'administer CAPTCHA settings',
        'recaptcha' => 'administer recaptcha',
      ];
      foreach ($permissions as $module => $permission) {
        if ($module_handler->moduleExists($module)) {
          $module_permissions += [$permission];
        }
      }
    }

    return $module_permissions;
  }

  abstract public function providerRoleExistNotExist(): array;

  abstract public function providerBasicPermissions(): array;

}
