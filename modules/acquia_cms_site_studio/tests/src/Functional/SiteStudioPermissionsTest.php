<?php

namespace Drupal\Tests\acquia_cms_site_studio\Functional;

use Drupal\Tests\acquia_cms_common\Functional\BasicPermissionsTest;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_site_studio
 * @group acquia_cms
 * @group risky
 */
class SiteStudioPermissionsTest extends BasicPermissionsTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_site_studio',
  ];

  /**
   * Get role permissions based on acms modules.
   *
   * @return array
   *   List of roles and permissions.
   */
  protected function getRolesPermissions(): array {
    // Return the role permissions data.
    return $this->rolePermissionsFacade->defaultRolePermissions('acquia_cms_site_studio');
  }

}
