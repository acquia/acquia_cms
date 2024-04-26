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
   * {@inheritdoc}
   */
  public function getFixtureBasePath(): string {
    return dirname(__DIR__) . "/fixtures/permissions/basic";
  }

  /**
   * Defines an array of role which shouldn't exists.
   */
  public function providerRoleExistNotExist(): array {
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
  public function providerBasicPermissions(): array {
    return [
      [
        'developer',
        $this->getPermissionsByRole('developer'),
      ],
    ];
  }

}
