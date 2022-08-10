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
