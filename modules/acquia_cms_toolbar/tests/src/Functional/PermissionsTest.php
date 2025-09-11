<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_cms_toolbar\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the PermissionManager class.
 *
 * @coversDefaultClass \Drupal\acquia_cms_toolbar\EntityOperations\PermissionManager
 * @group acquia_cms_toolbar
 */
class PermissionsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_toolbar',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalCreateRole([], 'content_administrator');
    $this->entityTypeManager = $this->container->get("entity_type.manager");
  }

  /**
   * Tests permissions are granted to content_administrator role.
   */
  public function testGrantPermissionsOnInstall(): void {
    // Load the role and check its permissions.
    $role_permissions = $this->entityTypeManager->getStorage('user_role')->load('content_administrator')->getPermissions();
    $this->assertEquals(['access toolbar'], $role_permissions);
  }

  /**
   * Tests permissions are granted to role created after module install.
   *
   * @param string $role
   *   The role.
   * @param array $permissions
   *   An array of permissions.
   *
   * @dataProvider rolePermissionDataProvider
   */
  public function testGrantPermissionsOnRoleCreate(string $role, array $permissions): void {
    // Create a new role.
    $this->drupalCreateRole([], $role);

    // Load the role and check its permissions.
    $role_permissions = $this->entityTypeManager->getStorage('user_role')->load('content_administrator')->getPermissions();
    $this->assertEquals($permissions, $role_permissions);
  }

  /**
   * Return an array of dataProvider for method grantPermissionToRoles.
   */
  public static function rolePermissionDataProvider(): array {
    return [
      [
        'content_author',
        ['access toolbar'],
      ],
      [
        'content_editor',
        ['access toolbar'],
      ],
      [
        'developer',
        ['access toolbar'],
      ],
      [
        'site_builder',
        ['access toolbar'],
      ],
      [
        'user_administrator',
        ['access toolbar'],
      ],
    ];
  }

}
