<?php

declare(strict_types=1);

namespace Drupal\acquia_cms_toolbar\EntityOperations;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to content entity events.
 *
 * @internal
 */
class PermissionManager implements EntityInsertOperationInterface, ContainerInjectionInterface {

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
    $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple($role_ids ?? [
      'content_administrator',
      'content_author',
      'content_editor',
      'developer',
      'site_builder',
      'user_administrator',
    ]);
    foreach ($roles as $role) {
      $role->grantPermission('access toolbar')->trustData()->save();
    }
  }

}
