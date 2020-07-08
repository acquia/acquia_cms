<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_common
 * @group acquia_cms
 */
class BasicPermissionsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_common',
    'media',
    'toolbar',
    'views',
  ];

  /**
   * Disable strict config schema checks in this test.
   *
   * Cohesion has a lot of config schema errors, and until they are all fixed,
   * this test cannot pass unless we disable strict config schema checking
   * altogether. Since strict config schema isn't critically important in
   * testing this functionality, it's okay to disable it for now, but it should
   * be re-enabled (i.e., this property should be removed) as soon as possible.
   *
   * @var bool
   */
  // @codingStandardsIgnoreStart
  protected $strictConfigSchema = FALSE;
  // @codingStandardsIgnoreEnd

  /**
   * Tests basic capabilities of our user roles.
   *
   * - Content authors, editors, and administrators should all be able to access
   *   the toolbar and the content overview.
   * - User administrator should be able to access the toolbar and the user
   *   overview.
   */
  public function testBasicPermissions() {
    $assert_session = $this->assertSession();

    $assert_toolbar = function () use ($assert_session) {
      $assert_session->elementExists('css', '#toolbar-administration');
    };

    $roles = [
      'content_author',
      'content_editor',
      'content_administrator',
    ];
    foreach ($roles as $role) {
      $account = $this->drupalCreateUser();
      $account->addRole($role);
      $account->save();

      // Only content administrators should be able to administer nodes, media,
      // or taxonomy.
      $is_administrator = $role === 'content_administrator';
      $this->assertSame($is_administrator, $account->hasPermission('administer nodes'));
      $this->assertSame($is_administrator, $account->hasPermission('administer media'));
      $this->assertSame($is_administrator, $account->hasPermission('administer taxonomy'));
      $this->assertSame($is_administrator, $account->hasPermission('bypass node access'));

      $this->drupalLogin($account);
      // All roles should have 'view the administration theme' permission.
      $this->assertTrue($account->hasPermission('view the administration theme'), "$role has view the administration theme permission");

      // All roles should be able to access the toolbar.
      $assert_toolbar();
      // All roles should be able to access the content and media overviews.
      $this->drupalGet('/admin/content');
      $assert_session->statusCodeEquals(200);
      $this->drupalGet('/admin/content/media');
      $assert_session->statusCodeEquals(200);
      $this->drupalLogout();
    }

    $account = $this->drupalCreateUser();
    $account->addRole('user_administrator');
    $account->save();
    $this->assertTrue($account->hasPermission('administer users'));

    $this->drupalLogin($account);
    $assert_toolbar();
    $this->drupalGet('/admin/people');
    $assert_session->statusCodeEquals(200);
    $this->drupalLogout();

    // Test non-content / Cohesion roles.
    $roles = Role::loadMultiple(['developer', 'site_builder']);
    // Assert both roles were loaded.
    $this->assertCount(2, $roles);
    foreach ($roles as $role) {
      // All roles should be able to access the toolbar.
      // @TODO: refactor this to be aligned with other toolbar assertions.
      $this->assertTrue($role->hasPermission('access toolbar'));
      // @TODO: refactor this to actually "test" the proper cohesion permissions once ACMS-216 is done.
      $this->assertTrue($role->hasPermission('use text format cohesion'));
    }
  }

}
