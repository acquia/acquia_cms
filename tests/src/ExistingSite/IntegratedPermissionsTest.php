<?php

namespace Drupal\Tests\acquia_cms\ExistingSite;

use Drupal\Tests\acquia_cms_common\Traits\PermissionsTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_common
 * @group acquia_cms
 */
class IntegratedPermissionsTest extends ExistingSiteBase {

  use PermissionsTrait;

  /**
   * {@inheritdoc}
   */
  public function getFixtureBasePath(): string {
    return dirname(__DIR__) . "/fixtures/permissions/integrated";
  }

  /**
   * Defines an array of role which shouldn't exists.
   */
  public function providerRoleExistNotExist(): array {
    return [
      [
        [
          "developer",
          "user_administrator",
          "site_builder",
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
      [
        'user_administrator',
        $this->getPermissionsByRole('user_administrator'),
      ],
      [
        'site_builder',
        $this->getPermissionsByRole('site_builder'),
      ],
      [
        'content_administrator',
        $this->getPermissionsByRole('content_administrator'),
      ],
      [
        'content_author',
        $this->getPermissionsByRole('content_author'),
      ],
      [
        'content_editor',
        $this->getPermissionsByRole('content_editor'),
      ],
      [
        'authenticated',
        $this->getPermissionsByRole('authenticated'),
      ],
    ];
  }

}
