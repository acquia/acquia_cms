<?php

declare(strict_types=1);

namespace Drupal\tests\acquia_cms_audio\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the PermissionManager class.
 *
 * @coversDefaultClass \Drupal\acquia_cms_audio\EntityOperations\PermissionManager
 * @group acquia_cms_audio
 */
class PermissionsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_audio'
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
    $this->drupalCreateRole([], 'content_author');
    $this->entityTypeManager = $this->container->get("entity_type.manager");
  }

  /**
   * Tests permissions are granted to role created before module install.
   */
  public function testGrantPermissionsOnInstall(): void {
    // Load the role and check its permissions.
    $role_permissions = $this->entityTypeManager->getStorage('user_role')->load('content_author')->getPermissions();
    $permissions = [
      'create audio media',
      'delete own audio media',
      'edit own audio media',
    ];
    $this->assertEquals($permissions, $role_permissions);
  }

  /**
   * Tests permissions are granted to role created after module install.
   */
  public function testGrantPermissionsOnRoleCreate(): void {
    // Create a new role.
    $this->drupalCreateRole([], 'content_editor');

    // Load the role and check its permissions.
    $role_permissions = $this->entityTypeManager->getStorage('user_role')->load('content_editor')->getPermissions();
    $permissions = [
      'delete any audio media',
      'edit any audio media',
    ];
    $this->assertEquals($permissions, $role_permissions);
  }

}
