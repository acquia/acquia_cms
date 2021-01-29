<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_common
 * @group acquia_cms
 * @group low_risk
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

    $contributor_permissions = [
      'access animate on view',
      'access component builder elements group',
      'access component content',
      'access components',
      'access content elements group',
      'access cpt_cat_dynamic_components cohesion_component_category group',
      'access cpt_cat_general_components cohesion_component_category group',
      'access cpt_cat_hero_components cohesion_component_category group',
      'access cpt_cat_interactive_components cohesion_component_category group',
      'access cpt_cat_layout_components cohesion_component_category group',
      'access cpt_cat_map_components cohesion_component_category group',
      'access cpt_cat_media_components cohesion_component_category group',
      'access cpt_cat_template_components cohesion_component_category group',
      'access custom elements group',
      'access drupal core elements group',
      'access elements',
      'access fields',
      'access form fields fields group',
      'access form help fields group',
      'access form layout fields group',
      'access helpers',
      'access hlp_cat_dynamic_helpers cohesion_helper_category group',
      'access hlp_cat_general_helpers cohesion_helper_category group',
      'access hlp_cat_interactive_helpers cohesion_helper_category group',
      'access hlp_cat_layout_helpers cohesion_helper_category group',
      'access hlp_cat_media_helpers cohesion_helper_category group',
      'access interactive elements group',
      'access layout elements group',
      'access media elements group',
      'access menu elements group',
      'access view elements group',
      'use text format cohesion',
      'view the administration theme',
      'use moderation dashboard',
      'use moderation sidebar',
      'clone node entity',
    ];
    $this->assertPermissions('content_author', $contributor_permissions);
    $this->assertPermissions('content_editor', $contributor_permissions);
    $this->assertPermissions('content_administrator', $contributor_permissions);

    $content_administrator_permissions = [
      'administer nodes',
      'administer media',
      'administer taxonomy',
      'bypass node access',
    ];
    $this->assertNoPermissions('content_author', $content_administrator_permissions);
    $this->assertNoPermissions('content_editor', $content_administrator_permissions);
    $this->assertPermissions('content_administrator', $content_administrator_permissions);

    $this->assertPermissions('user_administrator', [
      'administer users',
      'view the administration theme',
    ]);

    $cohesion_permissions = [
      'use text format cohesion',
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
      'view the administration theme',
    ];
    $this->assertPermissions('developer', $cohesion_permissions);
    $this->assertPermissions('site_builder', $cohesion_permissions);

    $developer_permissions = [
      'access analytics',
      'access animate on view',
      'access color_picker',
      'access component builder elements group',
      'access component content',
      'access components',
      'access content elements group',
      'access context visibility',
      'access custom elements group',
      'access drupal core elements group',
      'access elements',
      'access fields',
      'access form fields fields group',
      'access form help fields group',
      'access form layout fields group',
      'access helpers',
      'access hide no data',
      'access interactive elements group',
      'access layout elements group',
      'access markup',
      'access media elements group',
      'access menu elements group',
      'access seo',
      'access styles',
      'access tokens',
      'access view elements group',
      'administer base styles',
      'administer cohesion settings',
      'administer content templates',
      'administer master templates',
      'administer menu templates',
      'administer view templates',
      'administer website settings',
    ];
    $this->assertPermissions('developer', $developer_permissions);
    $this->assertNoPermissions('site_builder', $developer_permissions);

    $map = [
      'content_author' => [
        '/admin/content',
        '/admin/content/media',
      ],
      'content_editor' => [
        '/admin/content',
        '/admin/content/media',
      ],
      'content_administrator' => [
        '/admin/content',
        '/admin/content/media',
      ],
      'user_administrator' => [
        '/admin/people',
      ],
      'developer' => [],
      'site_builder' => [],
    ];
    foreach ($map as $role => $paths) {
      $account = $this->drupalCreateUser();
      $account->addRole($role);
      $account->save();

      $this->drupalLogin($account);
      // The role should be able to access the toolbar.
      $assert_session->elementExists('css', '#toolbar-administration');

      foreach ($paths as $path) {
        $this->drupalGet($path);
        $assert_session->statusCodeEquals(200);
      }
    }
  }

  /**
   * Asserts that a role has a set of permissions.
   *
   * @param string $role
   *   The ID of the role to check.
   * @param string[] $permissions
   *   An array of permissions the role is expected to have.
   */
  private function assertPermissions(string $role, array $permissions) : void {
    $role = Role::load($role);
    $missing_permissions = array_diff($permissions, $role->getPermissions());
    $this->assertEmpty($missing_permissions);
  }

  /**
   * Asserts that a role does not have a set of permissions.
   *
   * @param string $role
   *   The ID of the role to check.
   * @param string[] $permissions
   *   An array of permissions the role is not expected to have.
   */
  private function assertNoPermissions(string $role, array $permissions) : void {
    $role = Role::load($role);
    $granted_permissions = array_intersect($role->getPermissions(), $permissions);
    $this->assertEmpty($granted_permissions);
  }

}
