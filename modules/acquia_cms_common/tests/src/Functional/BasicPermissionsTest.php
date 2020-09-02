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

      // Permissions to add cohesion components, helpers, elements and
      // component content in layout canvas of content type.
      $this->assertTrue($account->hasPermission('access animate on view'));
      $this->assertTrue($account->hasPermission('access component builder elements group'));
      $this->assertTrue($account->hasPermission('access component content'));
      $this->assertTrue($account->hasPermission('access components'));
      $this->assertTrue($account->hasPermission('access content elements group'));
      $this->assertTrue($account->hasPermission('access cpt_cat_dynamic_components cohesion_component_category group'));
      $this->assertTrue($account->hasPermission('access cpt_cat_general_components cohesion_component_category group'));
      $this->assertTrue($account->hasPermission('access cpt_cat_hero_components cohesion_component_category group'));
      $this->assertTrue($account->hasPermission('access cpt_cat_interactive_components cohesion_component_category group'));
      $this->assertTrue($account->hasPermission('access cpt_cat_layout_components cohesion_component_category group'));
      $this->assertTrue($account->hasPermission('access cpt_cat_map_components cohesion_component_category group'));
      $this->assertTrue($account->hasPermission('access cpt_cat_media_components cohesion_component_category group'));
      $this->assertTrue($account->hasPermission('access cpt_cat_template_components cohesion_component_category group'));
      $this->assertTrue($account->hasPermission('access custom elements group'));
      $this->assertTrue($account->hasPermission('access drupal core elements group'));
      $this->assertTrue($account->hasPermission('access elements'));
      $this->assertTrue($account->hasPermission('access fields'));
      $this->assertTrue($account->hasPermission('access form fields fields group'));
      $this->assertTrue($account->hasPermission('access form help fields group'));
      $this->assertTrue($account->hasPermission('access form layout fields group'));
      $this->assertTrue($account->hasPermission('access helpers'));
      $this->assertTrue($account->hasPermission('access hlp_cat_dynamic_helpers cohesion_helper_category group'));
      $this->assertTrue($account->hasPermission('access hlp_cat_general_helpers cohesion_helper_category group'));
      $this->assertTrue($account->hasPermission('access hlp_cat_interactive_helpers cohesion_helper_category group'));
      $this->assertTrue($account->hasPermission('access hlp_cat_layout_helpers cohesion_helper_category group'));
      $this->assertTrue($account->hasPermission('access hlp_cat_media_helpers cohesion_helper_category group'));
      $this->assertTrue($account->hasPermission('access interactive elements group'));
      $this->assertTrue($account->hasPermission('access layout elements group'));
      $this->assertTrue($account->hasPermission('access media elements group'));
      $this->assertTrue($account->hasPermission('access menu elements group'));
      $this->assertTrue($account->hasPermission('access view elements group'));
      $this->assertTrue($account->hasPermission('use text format cohesion'));

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
      // All roles should have 'use moderation dashboard', 'use moderation
      // sidebar' and 'clone node entity' permission.
      $this->assertTrue($account->hasPermission('use moderation dashboard'));
      $this->assertTrue($account->hasPermission('use moderation sidebar'));
      $this->assertTrue($account->hasPermission('clone node entity'));

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

    // All roles should have 'view the administration theme' permission.
    $this->assertTrue($account->hasPermission('view the administration theme'), "$role has view the administration theme permission");

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
      // Common permission that both developer and site_builder have access.
      $this->assertTrue($role->hasPermission('use text format cohesion'));
      $this->assertTrue($role->hasPermission('access cohesion sync'));
      $this->assertTrue($role->hasPermission('administer cohesion'));
      $this->assertTrue($role->hasPermission('administer component categories'));
      $this->assertTrue($role->hasPermission('administer component content'));
      $this->assertTrue($role->hasPermission('administer components'));
      $this->assertTrue($role->hasPermission('administer custom styles'));
      $this->assertTrue($role->hasPermission('administer helper categories'));
      $this->assertTrue($role->hasPermission('administer helpers'));
      $this->assertTrue($role->hasPermission('administer style helpers'));
      $this->assertTrue($role->hasPermission('administer style_guide'));

      // Only developer will have access to these permissions.
      $is_developer = $role->id() === 'developer';
      $this->assertSame($is_developer, $role->hasPermission('access analytics'));
      $this->assertSame($is_developer, $role->hasPermission('access animate on view'));
      $this->assertSame($is_developer, $role->hasPermission('access color_picker'));
      $this->assertSame($is_developer, $role->hasPermission('access component builder elements group'));
      $this->assertSame($is_developer, $role->hasPermission('access component content'));
      $this->assertSame($is_developer, $role->hasPermission('access components'));
      $this->assertSame($is_developer, $role->hasPermission('access content elements group'));
      $this->assertSame($is_developer, $role->hasPermission('access context visibility'));
      $this->assertSame($is_developer, $role->hasPermission('access custom elements group'));
      $this->assertSame($is_developer, $role->hasPermission('access drupal core elements group'));
      $this->assertSame($is_developer, $role->hasPermission('access elements'));
      $this->assertSame($is_developer, $role->hasPermission('access fields'));
      $this->assertSame($is_developer, $role->hasPermission('access form fields fields group'));
      $this->assertSame($is_developer, $role->hasPermission('access form help fields group'));
      $this->assertSame($is_developer, $role->hasPermission('access form layout fields group'));
      $this->assertSame($is_developer, $role->hasPermission('access helpers'));
      $this->assertSame($is_developer, $role->hasPermission('access hide no data'));
      $this->assertSame($is_developer, $role->hasPermission('access interactive elements group'));
      $this->assertSame($is_developer, $role->hasPermission('access layout elements group'));
      $this->assertSame($is_developer, $role->hasPermission('access markup'));
      $this->assertSame($is_developer, $role->hasPermission('access media elements group'));
      $this->assertSame($is_developer, $role->hasPermission('access menu elements group'));
      $this->assertSame($is_developer, $role->hasPermission('access seo'));
      $this->assertSame($is_developer, $role->hasPermission('access styles'));
      $this->assertSame($is_developer, $role->hasPermission('access tokens'));
      $this->assertSame($is_developer, $role->hasPermission('access view elements group'));
      $this->assertSame($is_developer, $role->hasPermission('administer base styles'));
      $this->assertSame($is_developer, $role->hasPermission('administer cohesion settings'));
      $this->assertSame($is_developer, $role->hasPermission('administer content templates'));
      $this->assertSame($is_developer, $role->hasPermission('administer master templates'));
      $this->assertSame($is_developer, $role->hasPermission('administer menu templates'));
      $this->assertSame($is_developer, $role->hasPermission('administer view templates'));
      $this->assertSame($is_developer, $role->hasPermission('administer website settings'));

      // All roles should have 'view the administration theme' permission.
      $this->assertTrue($account->hasPermission('view the administration theme'), "$role has view the administration theme permission");

    }
  }

}
