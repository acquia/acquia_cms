<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

/**
 * Base class for content entity permissions.
 */
abstract class ContentPermissionsTestBase extends EntityPermissionsTestBase {

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
