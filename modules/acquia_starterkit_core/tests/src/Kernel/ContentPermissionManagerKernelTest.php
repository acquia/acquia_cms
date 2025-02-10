<?php

declare(strict_types=1);

namespace Drupal\tests\acquia_starterkit_core\Kernel;

use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\acquia_starterkit_core\Traits\ContentPermissionManagerTestTrait;
use Drupal\acquia_starterkit_core\EntityOperations\ContentPermissionManager;

/**
 * Tests the ContentPermissionManager class.
 *
 * @coversDefaultClass \Drupal\acquia_starterkit_core\EntityOperations\ContentPermissionManager
 * @group acquia_starterkit_core
 */
class ContentPermissionManagerKernelTest extends EntityKernelTestBase {

  use RecipeTestTrait;
  use ContentPermissionManagerTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['acquia_starterkit_core', 'node', 'media'];

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
    $this->contentPermissionManager = ContentPermissionManager::create($this->container);
  }

  /**
   * Tests the method entityInsert().
   *
   * @param array $entity_type_values
   *   An array of node type values.
   * @param array $roles_permissions
   *   An array of roles & permissions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @dataProvider assignRolePermissionsDataProvider
   */
  public function testAssignRolePermissionsOnEntityInsert(array $entity_type_values, array $roles_permissions): void {
    $entity_type = key($entity_type_values);

    $this->createRoles($roles_permissions);

    $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create($entity_type_values[$entity_type])
      ->save();

    $this->assertRolesPermissions($roles_permissions);
  }

  /**
   * Tests the method entityUpdate().
   *
   * @param array $entity_values
   *   An array of entity values.
   * @param array $roles_permissions
   *   An array of roles & permissions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @dataProvider assignRolePermissionsUpdateDataProvider
   */
  public function testAssignRolePermissionsOnEntityUpdate(array $entity_values, array $roles_permissions): void {
    $entity_type = $entity_values['entity_type'];

    $this->createRoles($roles_permissions);

    $node_type_storage = $this->container->get('entity_type.manager')
      ->getStorage($entity_type);

    $node_type = $node_type_storage->create($entity_values['create_values']);
    $node_type->save();

    $node_type_update = $node_type_storage->load($entity_values['create_values']['id']);

    $node_type_update = $node_type_update->set('original', $node_type);
    $node_type_update->setThirdPartySetting('acquia_starterkit_core', 'roles_permissions', $entity_values['updated_roles_permissions'])->save();
    $this->assertRolesPermissions($roles_permissions);
  }

  /**
   * Return an array of dataProvider for assignRolePermissionsOnEntityUpdate.
   */
  public static function assignRolePermissionsUpdateDataProvider(): array {
    return [
      [
        [
          'entity_type' => 'node_type',
          'create_values' => [
            'name' => 'Article',
            'type' => 'article',
            'id' => 'article',
            'third_party_settings' => [
              'acquia_starterkit_core' => [
                'roles_permissions' => [
                  'authenticated' => [
                    'grant_permissions' => [],
                  ],
                ],
              ],
            ],
          ],
          'updated_roles_permissions' => [
            'authenticated' => [
              'grant_permissions' => ['create article content'],
            ],
          ],
        ],
        [
          'authenticated' => ['grant_permissions' => ['create article content']],
        ],
      ],
      [
        [
          'entity_type' => 'media_type',
          'create_values' => [
            'label' => 'Image',
            'id' => 'image',
            'source' => 'image',
            'third_party_settings' => [
              'acquia_starterkit_core' => [
                'roles_permissions' => [
                  'authenticated' => [
                    'grant_permissions' => [],
                  ],
                ],
              ],
            ],
          ],
          'updated_roles_permissions' => [
            'authenticated' => [
              'grant_permissions' => ['create image media'],
            ],
          ],
        ],
        [
          'authenticated' => ['grant_permissions' => ['create image media']],
        ],
      ],
    ];
  }

  /**
   * Return an array of dataProvider for method assignRolePermissions.
   */
  public static function assignRolePermissionsDataProvider(): array {
    return [
      [
        [
          'node_type' => [
            'name' => 'Article',
            'type' => 'article',
            'third_party_settings' => [
              'acquia_starterkit_core' => ['roles_permissions' => self::getRolesPermissionsByEntity("article", "content")],
            ],
          ],
        ],
        self::getRolesPermissionsByEntity("article", "content"),
      ],
      [
        [
          'media_type' => [
            'label' => 'Image',
            'id' => 'image',
            'source' => 'image',
            'third_party_settings' => [
              'acquia_starterkit_core' => ['roles_permissions' => self::getRolesPermissionsByEntity("image", "media")],
            ],
          ],
        ],
        self::getRolesPermissionsByEntity("image", "media"),
      ],
    ];
  }

}
