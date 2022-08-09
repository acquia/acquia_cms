<?php

namespace Drupal\acquia_cms_common\Facade;

use Drupal\acquia_cms_common\Traits\SiteStudioPermissionTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a facade for integrating with Metatag.
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
  public function addRole(string $role, array $configurations = []) {
    $permissions = $configurations['permissions'] ?? $this->getPermissionsByRole($role);
    if (!($this->roleEntity->load($role) instanceof RoleInterface)) {
      $defaults = $this->defaultPermissionConfig($role, $configurations);
      $defaults["permissions"] = $permissions;
      $this->roleEntity->create($defaults)->save();
    }
  }

  /**
   * Updates an existing role.
   *
   * @param string $role
   *   A new role to add.
   * @param array $configurations
   *   An array of configurations.
   */
  public function updateRole(string $role, array $configurations = []) {
    $permissions = $configurations['permissions'] ?? $this->getPermissionsByRole($role);
    user_role_grant_permissions($role, $permissions);
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
        ]),
      'site_builder' => [
        'access cohesion sync',
        'administer cohesion',
        'administer component categories',
        'administer component content',
        'administer components',
        'administer custom styles',
        'administer helper categories',
        'administer helpers',
        'administer style helpers',
        'administer style_guide',
        'use text format cohesion',
      ],
    ];
    if ($this->moduleHandler->moduleExists('acquia_cms_tour')) {
      $allPermissions["content_administrator"] = array_merge($allPermissions["content_administrator"], [
        'access acquia cms tour',
        'access acquia cms tour dashboard',
      ]);
      array_push($allPermissions["content_author"], 'access acquia cms tour');
      array_push($allPermissions["content_editor"], 'access acquia cms tour');
    }

    if ($this->moduleHandler->moduleExists('acquia_cms_site_studio')) {
      $allPermissions["content_editor"] = array_merge($allPermissions["content_editor"], self::basicComponentCategoryHelperPermissions());
      $ssAdminPermissions = array_merge(
        self::basicComponentPermissions(),
        self::basicComponentCategoryHelperPermissions(),
        self::additionalComponentHelperPermissions(),
        self::additionalComponentCategoryPermissions(),
        ['access visual page builder'],
      );
      $allPermissions["content_administrator"] = array_merge($allPermissions["content_administrator"], $ssAdminPermissions);
      $allPermissions["content_editor"] = array_merge($allPermissions["content_editor"], $ssAdminPermissions);
    }
    if ($this->moduleHandler->moduleExists('shield')) {
      $allPermissions["user_administrator"] = ['administer shield'];
    }
    return $allPermissions[$role_name] ?? [];
  }

}
