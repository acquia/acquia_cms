<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

use Drupal\Tests\acquia_cms_common\Traits\EntityPermissionsTrait;

/**
 * Base class for content entity permissions.
 */
abstract class ContentPermissionsTestBase extends EntityPermissionsTestBase {

  use EntityPermissionsTrait;

  /**
   * Defines an array of role which should & shouldn't exist.
   */
  public function providerRoleExistNotExist(): array {
    return [
      [
        [
          "content_administrator",
          "content_author",
          "content_editor",
        ],
        [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): string {
    return "content";
  }

}
