<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

use Drupal\Tests\acquia_cms_common\Traits\PermissionsTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Base class for entity permissions.
 */
abstract class EntityPermissionsTestBase extends BrowserTestBase {

  use PermissionsTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  protected function getFixtureBasePath(): string {
    $entityType = $this->getEntityType();
    return dirname(__DIR__) . "/fixtures/permissions/$entityType";
  }

  /**
   * {@inheritdoc}
   */
  public function providerBasicPermissions(): array {
    $entityType = $this->getEntityType();
    $bundle = $this->getBundle();
    return [
      [
        'content_administrator',
        $this->getPermissionsByRole('content_administrator'),
      ],
      [
        'content_author',
        array_merge([
          "create $bundle $entityType",
          "delete own $bundle $entityType",
          "edit own $bundle $entityType",
        ], $this->getPermissionsByRole('content_author'),
        ),
      ],
      [
        'content_editor',
        array_merge([
          "delete any $bundle $entityType",
          "edit any $bundle $entityType",
        ], $this->getPermissionsByRole('content_editor')
        ),
      ],
    ];
  }

  /**
   * Function to get entity type.
   *
   * @return string
   *   Returns entity_type. Ex: content, media etc.
   */
  abstract public function getEntityType(): string;

  /**
   * Function to get entity bundle.
   *
   * @return string
   *   Returns bundle of entity. Ex: article, image etc.
   */
  abstract public function getBundle(): string;

}
