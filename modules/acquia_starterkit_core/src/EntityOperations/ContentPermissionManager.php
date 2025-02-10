<?php

declare(strict_types=1);

namespace Drupal\acquia_starterkit_core\EntityOperations;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to content entity events.
 *
 * @internal
 */
class ContentPermissionManager implements EntityInsertOperationInterface, EntityUpdateOperationInterface, ContainerInjectionInterface {

  /**
   * Constructs the ContentPermissionManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The configuration manager.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function entityInsert(ConfigEntityInterface $entity): void {
    // We are explicitly setting the entity to be new on hook_entity_insert().
    $entity->enforceIsNew();
    if ($this->shouldSkipOperations($entity)) {
      return;
    }
    $roles_permissions = $this->getRolesPermissions($entity);
    $this->assignPermissions($roles_permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function entityUpdate(ConfigEntityInterface $entity): void {
    if ($this->shouldSkipOperations($entity)) {
      return;
    }
    $before_roles_permissions = $this->getRolesPermissions($entity->original);
    $current_roles_permissions = $this->getRolesPermissions($entity);
    $roles_permissions = $this->findNewRolesAndPermissions($before_roles_permissions, $current_roles_permissions);
    $this->assignPermissions($roles_permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function shouldSkipOperations(ConfigEntityInterface $entity): bool {
    $roles_permissions = $this->getRolesPermissions($entity);
    if (!$roles_permissions || $entity->isSyncing()) {
      return TRUE;
    }
    if (!$entity->isNew() && $entity->get('original') instanceof ConfigEntityInterface) {
      $before_roles_permissions = $this->getRolesPermissions($entity->get('original'));
      $current_roles_permissions = $this->getRolesPermissions($entity);
      if ($before_roles_permissions === $current_roles_permissions) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Assigns permissions to the roles.
   *
   * @param array $roles_permissions
   *   The roles and permissions to assign.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Config\Entity\ConfigEntityInterface
   */
  private function assignPermissions(array $roles_permissions): void {
    $user_role = $this->entityTypeManager->getStorage('user_role');
    foreach ($roles_permissions as $role => $data) {
      $permissions = $data['grant_permissions'] ?? [];
      if (!$permissions) {
        return;
      }
      $roleObj = $user_role->load($role);
      if ($roleObj instanceof RoleInterface) {
        array_walk($permissions, $roleObj->grantPermission(...));
        $roleObj->trustData()->save();
      }
    }
  }

  /**
   * Finds the new roles and permissions or new permissions in existing roles.
   *
   * @param array $before
   *   The roles and permissions before the entity was updated.
   * @param array $current
   *   The roles and permissions after the entity was updated.
   */
  protected function findNewRolesAndPermissions(array $before, array $current): array {
    $differences = [];

    foreach ($current as $role => $permissionsData) {
      // Check if role exists in current entity.
      if (!isset($before[$role])) {
        // If the role doesn't exist before entity was saved,
        // add all its permissions to the differences.
        $differences[$role] = $permissionsData;
      }
      else {
        // If the role exists, compare permissions.
        $newPermissions = array_diff($permissionsData['grant_permissions'], $before[$role]['grant_permissions']);
        if (!empty($newPermissions)) {
          $differences[$role]['grant_permissions'] = $newPermissions;
        }
      }
    }

    return $differences;
  }

  /**
   * Returns the list of roles and permissions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The given entity object.
   */
  protected function getRolesPermissions(ConfigEntityInterface $entity): array {
    return $entity->getThirdPartySettings('acquia_starterkit_core')['roles_permissions'] ?? [];
  }

}
