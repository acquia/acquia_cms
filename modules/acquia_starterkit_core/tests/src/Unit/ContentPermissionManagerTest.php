<?php

declare(strict_types=1);

namespace Drupal\tests\acquia_starterkit_core\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\acquia_starterkit_core\EntityOperations\ContentPermissionManager;

/**
 * Tests the ContentPermissionManager class.
 *
 * @coversDefaultClass \Drupal\acquia_starterkit_core\EntityOperations\ContentPermissionManager
 * @group acquia_starterkit_core
 */
class ContentPermissionManagerTest extends UnitTestCase {

  /**
   * Holds an object of ContentPermissionManager class.
   *
   * @var \Drupal\acquia_starterkit_core\EntityOperations\ContentPermissionManager
   */
  protected $contentPermissionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entity_type_manager = $this->createMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $container->method('get')
      ->with('entity_type.manager')
      ->willReturn($entity_type_manager);

    $this->contentPermissionManager = ContentPermissionManager::create($container);
  }

  /**
   * Tests the method shouldSkipOperations().
   *
   * @param bool $isSyncing
   *   Determine if configuration is syncing.
   * @param array $roles_permissions
   *   Holds data of users and their permissions.
   * @param bool $expected
   *   Returns expected value.
   *
   * @dataProvider provideShouldSkipOperationsCases
   *
   * @throws \PHPUnit\Framework\MockObject\Exception
   */
  public function testShouldSkipOperations(bool $isSyncing, array $roles_permissions, bool $expected): void {
    $entity = $this->createMock('Drupal\media\MediaTypeInterface');

    $entity->method('getThirdPartySettings')
      ->with('acquia_starterkit_core')
      ->willReturn($roles_permissions);

    $entity->method('isSyncing')->willReturn($isSyncing);

    $this->assertSame($expected, $this->contentPermissionManager->shouldSkipOperations($entity));
  }

  /**
   * Tests the method findNewRolesAndPermissions().
   *
   * @dataProvider rolesAndPermissionsProvider
   */
  public function testFindNewRolesAndPermissions(array $before_roles_permissions, array $after_roles_permissions, array $expected): void {
    // Use reflection to access the protected method.
    $method = new \ReflectionMethod(ContentPermissionManager::class, 'findNewRolesAndPermissions');
    $method->setAccessible(TRUE);

    // Call the method and assert the result.
    $result = $method->invoke($this->contentPermissionManager, $before_roles_permissions, $after_roles_permissions);
    $this->assertEqualsCanonicalizing($expected, $result);
  }

  /**
   * Provides test cases for finding new roles and permissions.
   */
  public static function rolesAndPermissionsProvider(): array {
    return [
      // Case 1:When existing role permission has new permissions.
      [
        'before_roles_permissions' => [
          "content_author" => [
            "grant_permissions" => [
              "create article content",
              "edit own article content",
            ],
          ],
        ],
        'after_roles_permissions' => [
          "content_author" => [
            "grant_permissions" => [
              "create article content",
              "edit own article content",
              "delete own article content",
            ],
          ],
        ],
        'expected' => [
          "content_author" => [
            "grant_permissions" => ["delete own article content"],
          ],
        ],
      ],
      // Case 2:When existing role permission array has new role & permissions.
      [
        'before_roles_permissions' => [
          "content_author" => [
            "grant_permissions" => [
              "create article content",
            ],
          ],
        ],
        'after_roles_permissions' => [
          "content_author" => [
            "grant_permissions" => [
              "create article content",
            ],
          ],
          "content_editor" => [
            "grant_permissions" => [
              "edit any article content",
            ],
          ],
        ],
        'expected' => [
          "content_editor" => [
            "grant_permissions" => ["edit any article content"],
          ],
        ],
      ],
      // Case 3: No new roles or permissions array has changed.
      [
        'before_roles_permissions' => [
          "content_author" => [
            "grant_permissions" => [
              "create article content",
              "edit own article content",
            ],
          ],
        ],
        'after_roles_permissions' => [
          "content_author" => [
            "grant_permissions" => [
              "create article content",
              "edit own article content",
            ],
          ],
        ],
        'expected' => [],
      ],
    ];
  }

  /**
   * The dataProvider for testShouldSkipOperations.
   */
  public static function provideShouldSkipOperationsCases(): array {
    return [
      // When isSyncing is TRUE, should skip operations.
      'Entity is syncing' => [TRUE, ['roles_permissions' => ['content_author' => ['create article content']]], TRUE],

      // When isSyncing is FALSE and rolesPermissions are empty,
      // we should skip operations.
      'Entity is not syncing but roles permissions are empty' => [FALSE, [], TRUE],

      // When isSyncing is FALSE, rolesPermissions is not empty,
      // we should not skip operations.
      'Entity is not syncing and roles permissions are not empty' => [FALSE, ['roles_permissions' => ['content_author' => ['create article content']]], FALSE],
    ];
  }

}
