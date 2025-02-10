<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_starterkit_core\Traits;

/**
 * Trait for common methods used in ContentPermissionManager tests.
 */
trait ContentPermissionManagerTestTrait {

  /**
   * Holds an object of ContentPermissionManager class.
   *
   * @var \Drupal\acquia_starterkit_core\EntityOperations\ContentPermissionManager
   */
  protected $contentPermissionManager;

  /**
   * Create roles from given array of roles & permissions.
   *
   * @param array $roles_permissions
   *   An array of roles & permissions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createRoles(array $roles_permissions): void {
    $user_role_storage = $this->container->get('entity_type.manager')
      ->getStorage('user_role');
    foreach ($roles_permissions as $role => $permissions) {
      $role = $user_role_storage->create(['id' => $role, 'label' => $this->randomString()]);
      $role->save();
    }
  }

  /**
   * Assert the given array of roles & permissions.
   *
   * @param array $roles_permissions
   *   An array of roles & permissions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function assertRolesPermissions(array $roles_permissions): void {
    $user_role_storage = $this->container->get('entity_type.manager')
      ->getStorage('user_role');
    foreach ($roles_permissions as $role => $permissions) {
      $roleObject = $user_role_storage->load($role);
      $this->assertSame($roleObject->getPermissions(), $permissions['grant_permissions']);
    }
  }

  /**
   * Returns an array of roles & permissions.
   *
   * @param string $bundle
   *   Given bundle.
   * @param string $entity_type
   *   Given entity type.
   */
  public static function getRolesPermissionsByEntity(string $bundle, string $entity_type): array {
    return [
      "content_author" => [
        "grant_permissions" => [
          "create $bundle $entity_type",
          "delete own $bundle $entity_type",
          "edit own $bundle $entity_type",
        ],
      ],
      'content_editor' => [
        "grant_permissions" => [
          "delete any $bundle $entity_type",
          "edit any $bundle $entity_type",
        ],
      ],
    ];
  }

}
