<?php

declare(strict_types=1);

namespace Drupal\acquia_cms_video\EntityOperations;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to content entity events.
 *
 * @internal
 */
class PermissionManager implements PermissionManagerInterface, ContainerInjectionInterface {

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
   * {@inheritdoc}
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
            'create video media',
            'edit own video media',
            'delete own video media',
          ] as $permission) {
            $role->grantPermission($permission);
          }
          $role->trustData()->save();
          break;

        case 'content_editor':
          foreach (['edit any video media', 'delete any video media'] as $permission) {
            $role->grantPermission($permission);
          }
          $role->trustData()->save();
          break;
      }
    }
  }

}
