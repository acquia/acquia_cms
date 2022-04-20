<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_common
 * @group acquia_cms
 * @group risky
 */
class SiteStudioElementsPermissionsTest extends BrowserTestBase {

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
   * - Content authors, and Content administrators should all be able to access
   *   the site studio elements in canvas.
   */
  public function testSiteStudioElementsPermissions() {
    // Site Studio element permissions test.
    $site_studio_elements_permissions = [
      'access cpt_cat_accordion_components cohesion_component_category group',
      'access cpt_cat_basic_components cohesion_component_category group',
      'access cpt_cat_card_components cohesion_component_category group',
      'access cpt_cat_feature_sections cohesion_component_category group',
      'access cpt_cat_read_more_components cohesion_component_category group',
      'access cpt_cat_slider_components cohesion_component_category group',
      'access cpt_cat_tab_components cohesion_component_category group',
      'access hlp_cat_hero_sections cohesion_helper_category group',
      'access hlp_cat_miscellaneous cohesion_helper_category group',
      'access hlp_cat_page_layouts cohesion_helper_category group',
      'access hlp_cat_slider_sections cohesion_helper_category group',
      'access hlp_cat_text_sections cohesion_helper_category group',
      'access visual page builder',
    ];
    $this->assertPermissions('content_author', $site_studio_elements_permissions);
    $this->assertPermissions('content_administrator', $site_studio_elements_permissions);

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

}
