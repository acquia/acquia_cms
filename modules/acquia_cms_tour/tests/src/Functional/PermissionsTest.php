<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_cms_tour\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the PermissionManager class.
 *
 * @group acquia_cms_tour
 */
class PermissionsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_tour',
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
    $this->assertEquals(['access acquia cms tour dashboard'], $role_permissions);
  }

}
