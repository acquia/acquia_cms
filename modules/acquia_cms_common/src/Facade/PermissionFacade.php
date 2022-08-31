<?php

namespace Drupal\acquia_cms_common\Facade;

use Drupal\acquia_cms_common\Traits\SiteStudioPermissionTrait;
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

  use SiteStudioPermissionTrait;

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
   *   A new role to add.
   * @param array $configurations
   *   An array of configurations.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addRole(string $role, array $configurations = []) :int {
    if (!($this->roleEntity->load($role) instanceof RoleInterface)) {
      // When creating a new role, we need to add default permissions as well
      // that is needed for that particular role.
      $permissions = $this->getPermissionsByRole($role);
      $permissions = isset($configurations['permissions']) ? array_merge($permissions, $configurations['permissions']) : $permissions;
      $defaults = $this->defaultPermissionConfig($role, $configurations);
      $defaults["permissions"] = $permissions;
      return $this->roleEntity->create($defaults)->save();
    }
    return 0;
  }

  /**
   * Updates an existing role.
   *
   * @param string $role
   *   A new role to add.
   * @param array $configurations
   *   An array of configurations.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateRole(string $role, array $configurations = []) {
    if (($roleObject = $this->roleEntity->load($role)) instanceof RoleInterface) {
      $permissions = $this->getPermissionsByRole($role);
      // When update the role, we need sure that default permissions exists
      // If not, then we should add the default permissions.
      $permissions = isset($configurations['permissions']) ? array_merge($permissions, $configurations['permissions']) : $permissions;
      $permissions = array_diff($permissions, $roleObject->getPermissions());
      foreach ($permissions as $permission) {
        $roleObject->grantPermission($permission);
      }
      return $roleObject->save();
    }
  }

  /**
   * Create a new role (or update, if exists) & add permissions (if given).
   *
   * @param string $role
   *   A role to add or update.
   * @param array $configurations
   *   An array of configurations.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createOrUpdateRole(string $role, array $configurations = []) : void {
    if (!$this->addRole($role, $configurations)) {
      $this->updateRole($role, $configurations);
    }
  }

  /**
   * The default permission configuration.
   *
   * @param string $role
   *   A new role to add.
   * @param array $configurations
   *   An array of configurations.
   */
  protected function defaultPermissionConfig($role, array $configurations = []): array {
    $label = $configurations['label'] ?? ucwords(str_replace("_", " ", $role));
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

    // Check and prepare roles-permissions for acquia cms tour.
    if ($this->moduleHandler->moduleExists('acquia_cms_tour')) {
      $allPermissions["content_administrator"] = array_merge($allPermissions["content_administrator"], [
        'access acquia cms tour',
        'access acquia cms tour dashboard',
      ]);
      $allPermissions["content_author"][] = 'access acquia cms tour';
      $allPermissions["content_editor"][] = 'access acquia cms tour';
    }

    // Check and prepare roles-permissions for acquia cms site studio.
    if ($this->moduleHandler->moduleExists('acquia_cms_site_studio')) {
      $allPermissions["content_author"] = array_merge(
        $allPermissions["content_author"],
        self::getSiteStudioPermissionsByRole('content_author'),
      );
      $allPermissions["content_administrator"] = array_merge(
        $allPermissions["content_administrator"],
        self::getSiteStudioPermissionsByRole('content_administrator'),
      );
      $allPermissions["content_editor"] = array_merge(
        $allPermissions["content_editor"],
        self::getSiteStudioPermissionsByRole('content_editor'),
      );
      $allPermissions['site_builder'] = self::getSiteStudioPermissionsByRole('site_builder');
    }

    if ($this->moduleHandler->moduleExists('shield')) {
      $allPermissions["user_administrator"] = ['administer shield'];
    }

    return $allPermissions[$role_name] ?? [];
  }

}
