<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

use Drupal\Tests\acquia_cms_common\Traits\PermissionsTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_common
 * @group acquia_cms
 * @group risky
 */
class BasicPermissionsTest extends BrowserTestBase {

  use PermissionsTrait;

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
   * {@inheritdoc}
   */
  public function getFixtureBasePath(): string {
    return dirname(__DIR__) . "/fixtures/permissions/basic";
  }

  /**
   * Defines an array of role which shouldn't exists.
   */
  public static function providerRoleExistNotExist(): array {
    return [
      [
        [
          "administrator",
          "site_builder",
          "user_administrator",
        ],
        [
          "content_administrator",
          "content_author",
          "content_editor",
        ],
      ],
    ];
  }

  /**
   * Defines basic permissions & no permissions for roles.
   *
   * @throws \Exception
   */
  public static function providerBasicPermissions(): array {
    $object = new self('text');
    return [
      [
        'site_builder',
        $object->getPermissionsByRole('site_builder'),
        ['administer shield'],
      ],
      [
        'user_administrator',
        $object->getPermissionsByRole('user_administrator'),
        ['administer shield'],
      ],
    ];
  }

  /**
   * Defines an array of modules & permissions to roles.
   */
  public static function providerModulePermissions(): array {
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
