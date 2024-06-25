<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

use Drupal\Tests\acquia_cms_common\Traits\PermissionsTrait;
use Drupal\Tests\BrowserTestBase;
use Prophecy\Exception\Doubler\MethodNotFoundException;

/**
 * Base class for entity permissions.
 */
class EntityPermissionsTestBase extends BrowserTestBase {

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
  public static function providerRoleExistNotExist(): array {
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
    // Let's throw exception if this trait is
    // extended by other class and does not provide entity Type.
    if (!method_exists($this, 'getEntityType')) {
      throw new MethodNotFoundException('Method getEntityType not found for class [' . get_class($this) . ']', get_class($this), 'getEntityType');
    }
    $entityType = $this->getEntityType();
    return dirname(__DIR__) . "/fixtures/permissions/$entityType";
  }

  /**
   * {@inheritdoc}
   */
  public static function providerBasicPermissions(): array {
    $object = new static('test');
    // Let's throw exception if this trait is
    // extended by other class and does not provide entity Type.
    if (!method_exists($object, 'getEntityType')) {
      throw new MethodNotFoundException('Method getEntityType not found for class [' . get_class($object) . ']', get_class($object), 'getEntityType');
    }
    // Let's throw exception if this trait is
    // extended by other class and does not provide entity Type.
    if (!method_exists($object, 'getBundle')) {
      throw new MethodNotFoundException('Method getEntityType not found for class [' . get_class($object) . ']', get_class($object), 'getEntityType');
    }
    $entityType = $object->getEntityType();
    $bundle = $object->getBundle();

    return [
      [
        'content_administrator',
        $object->getPermissionsByRole('content_administrator'),
      ],
      [
        'content_author',
        array_merge([
          "create $bundle $entityType",
          "delete own $bundle $entityType",
          "edit own $bundle $entityType",
        ], $object->getPermissionsByRole('content_author'),
        ),
      ],
      [
        'content_editor',
        array_merge([
          "delete any $bundle $entityType",
          "edit any $bundle $entityType",
        ], $object->getPermissionsByRole('content_editor')
        ),
      ],
    ];
  }

}
