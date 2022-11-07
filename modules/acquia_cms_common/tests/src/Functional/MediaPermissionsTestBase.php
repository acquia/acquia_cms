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
          'site_builder',
          'user_administrator',
        ],
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
    return [
      [
        'site_builder',
        [
          'use text format filtered_html',
          'use text format full_html',
          'view the administration theme',
        ],
      ],
      [
        'user_administrator',
        [
          'administer seckit',
          'administer site configuration',
          'administer users',
          'manage password reset',
          'view the administration theme',
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
