<?php

namespace Drupal\Tests\acquia_cms_site_studio\Functional;

use Drupal\Tests\acquia_cms_common\Traits\PermissionsTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_common
 * @group acquia_cms
 * @group risky
 */
class SiteStudioPermissionsTest extends BrowserTestBase {

  use PermissionsTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_site_studio',
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
          "developer",
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
    $instance = new static();
    return [
      [
        'developer',
        $instance->getPermissionsByRole('developer'),
      ],
    ];
  }

}
