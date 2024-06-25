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
  public static function providerRoleExistNotExist(): array {
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
  public static function providerBasicPermissions(): array {
    $instance = new static();
    return [
      [
        'developer',
        $instance->getPermissionsByRole('developer'),
      ],
      [
        'user_administrator',
        $instance->getPermissionsByRole('user_administrator'),
      ],
      [
        'site_builder',
        $instance->getPermissionsByRole('site_builder'),
      ],
      [
        'content_administrator',
        $instance->getPermissionsByRole('content_administrator'),
      ],
      [
        'content_author',
        $instance->getPermissionsByRole('content_author'),
      ],
      [
        'content_editor',
        $instance->getPermissionsByRole('content_editor'),
      ],
      [
        'authenticated',
        $instance->getPermissionsByRole('authenticated'),
      ],
    ];
  }

}
