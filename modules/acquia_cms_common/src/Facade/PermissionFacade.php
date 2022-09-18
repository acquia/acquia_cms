<?php

namespace Drupal\acquia_cms_common\Facade;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a facade to provide usage for roles and permissions.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
class PermissionFacade implements ContainerInjectionInterface {

  /**
   * The config installer service.
   *
   * @var \Drupal\user\RoleInterface
   */
  private $roleEntity;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * The PermissionsFacade constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The config installer service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->roleEntity = $entity_type_manager->getStorage("user_role");
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * Create a new role.
   *
   * @param string $role
   *   A new role to add and adding permissions.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addRole(string $role) :int {
    if (!($this->roleEntity->load($role) instanceof RoleInterface)) {
      $defaults = $this->defaultPermissionConfig($role);
      // When creating a new role, we need to add default permissions as well
      // that is needed for that particular role.
      $defaults['permissions'] = $this->getPermissionsByRole($role);
      $roleObj = $this->roleEntity->create($defaults);
      $this->moduleHandler->alter('content_model_role_presave', $roleObj);
      return $roleObj->save();
    }
    return 0;
  }

  /**
   * Updates an existing role.
   *
   * @param string $role
   *   A role string where permissions need to be added/updated.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateRole(string $role) :int {
    if (($roleObject = $this->roleEntity->load($role)) instanceof RoleInterface) {
      $previousPermissions = $roleObject->getPermissions();
      $this->moduleHandler->alter('content_model_role_presave', $roleObject);
      $updatedPermissions = $roleObject->getPermissions();
      if ($previousPermissions !== $updatedPermissions) {
        $permissions = array_diff($updatedPermissions, $previousPermissions);
        foreach ($permissions as $permission) {
          $roleObject->grantPermission($permission);
        }
        return $roleObject->save();
      }
    }
    return 0;
  }

  /**
   * Create a new role (or update, if exists) & add permissions (if given).
   *
   * @param string $role
   *   A role to add or update.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createOrUpdateRole(string $role) : void {
    if (!$this->addRole($role)) {
      $this->updateRole($role);
    }
  }

  /**
   * The default permission configuration.
   *
   * @param string $role
   *   Gets default configuration for the given role.
   */
  protected function defaultPermissionConfig(string $role): array {
    $label = ucwords(str_replace("_", " ", $role));
    return [
      "langcode" => "en",
      "status" => TRUE,
      "dependencies" => [],
      "id" => $role,
      "label" => $label,
      "weight" => $configurations['weight'] ?? 0,
      "is_admin" => NULL,
    ];
  }

  /**
   * Gets common permissions for following roles.
   *
   * Content_author, content_editor, content_administrator.
   */
  protected function getBasicAdministerPermissions(): array {
    return [
      'access content overview',
      'access media overview',
      'clone node entity',
      'use editorial transition create_new_draft',
      'use editorial transition review',
      'use moderation dashboard',
      'use moderation sidebar',
      'view latest version',
      'use text format filtered_html',
      'use text format full_html',
      'view the administration theme',
    ];
  }

  /**
   * Function to get all permissions by role.
   *
   * @param string $role_name
   *   A param to find permissions of given role.
   */
  protected function getPermissionsByRole(string $role_name): array {
    $allPermissions = [
      "content_administrator" => array_merge(
        $this->getBasicAdministerPermissions(), [
          'access taxonomy overview',
          'administer media',
          'administer moderation sidebar',
          'administer nodes',
          'administer taxonomy',
          'bypass node access',
          'schedule publishing of nodes',
          'use editorial transition archived_published',
          'use editorial transition publish',
          'view any moderation dashboard',
          'view any unpublished content',
          'view scheduled content',
          'use editorial transition archive',
        ]),
      "content_author" => array_merge(
        $this->getBasicAdministerPermissions(), [
          'administer menu',
          'view own unpublished content',
        ]),
      "content_editor" => array_merge(
        $this->getBasicAdministerPermissions(), [
          'schedule publishing of nodes',
          'use editorial transition publish',
          'view any unpublished content',
          'view scheduled content',
          'use editorial transition archive',
        ]),
    ];
    return $allPermissions[$role_name] ?? [];
  }

}
