<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

/**
 * Base class for media entity permissions.
 */
abstract class MediaPermissionsTestBase extends EntityPermissionsTestBase {

  /**
   * Defines an array of role which should & shouldn't exist.
   */
  public function providerRoleExistNotExist(): array {
    return [
      [
        [
          "content_author",
          "content_editor",
          "content_administrator",
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function providerBasicPermissions(): array {
    $entityType = $this->getEntityType();
    $bundle = $this->getBundle();
    return [
      [
        'content_author',
        [
          "create $bundle $entityType",
          "delete own $bundle $entityType",
          "edit own $bundle $entityType",
        ],
      ],
      [
        'content_editor',
        [
          "delete any $bundle $entityType",
          "edit any $bundle $entityType",
        ],
      ],
      [
        'content_administrator',
        [
          "administer media",
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): string {
    return "media";
  }

}
