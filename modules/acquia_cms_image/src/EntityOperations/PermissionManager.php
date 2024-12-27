<?php

declare(strict_types=1);

namespace Drupal\acquia_cms_image\EntityOperations;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to content entity events.
 *
 * @internal
 */
class PermissionManager implements ContainerInjectionInterface {

  /**
   * Constructs the PermissionManager object.
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
   * Update role permission handler.
   *
   * @param array|null $role_ids
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function grantPermissionToRoles(array $role_ids = NULL): void {
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple($role_ids ?? [
      'content_author',
      'content_editor',
    ]);
    foreach ($roles as $role) {
      switch ($role->id()) {
        case 'content_author':
          foreach ([
            'create image media',
            'edit own image media',
            'delete own image media',
          ] as $permission) {
            $role->grantPermission($permission);
          }
          $role->trustData()->save();
          break;

        case 'content_editor':
          foreach (['edit any image media', 'delete any image media'] as $permission) {
            $role->grantPermission($permission);
          }
          $role->trustData()->save();
          break;
      }
    }
  }

}
