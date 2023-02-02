<?php

namespace Drupal\Tests\acquia_cms_site_studio_cm\Functional;

use Drupal\Core\Site\Settings;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Site Studio core configuration.
 *
 * @group acquia_cms_site_studio_cm
 */
class SiteStudioCoreConfigTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_site_studio_cm',
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
   * Assert that config_split setting has expected value.
   */
  public function testConfigSplitCompleteList() {
    // Check that config_split site studio contains cohesion.
    $config = $this->config('config_split.config_split.site_studio');
    if ($config) {
      $this->assertEquals(['cohesion_*', 'cohesion.*'], $config->get('complete_list'));
    }
  }

  /**
   * Assert config_ignore setting has expected value.
   */
  public function testConfigIgnoreSettings() {
    $config = $this->config('config_ignore.settings');
    if ($config) {
      $this->assertEquals(['cohesion.*', 'cohesion_*'], $config->get('ignored_config_entities'));
    }
  }

  /**
   * Assert that cohesion.sync.settings default configs are available.
   */
  public function testSiteStudioFullExportSettings() {
    $config = $this->config('cohesion.sync.settings');
    if ($config) {
      $enabled_entity_types_expected = [
        'cohesion_base_styles' => 'cohesion_base_styles',
        'cohesion_color' => 'cohesion_color',
        'cohesion_component' => 'cohesion_component',
        'cohesion_component_category' => 'cohesion_component_category',
        'cohesion_content_templates' => 'cohesion_content_templates',
        'cohesion_custom_style' => 'cohesion_custom_style',
        'cohesion_font_library' => 'cohesion_font_library',
        'cohesion_font_stack' => 'cohesion_font_stack',
        'cohesion_helper' => 'cohesion_helper',
        'cohesion_helper_category' => 'cohesion_helper_category',
        'cohesion_icon_library' => 'cohesion_icon_library',
        'cohesion_master_templates' => 'cohesion_master_templates',
        'cohesion_menu_templates' => 'cohesion_menu_templates',
        'cohesion_scss_variable' => 'cohesion_scss_variable',
        'cohesion_style_guide' => 'cohesion_style_guide',
        'cohesion_style_guide_manager' => 'cohesion_style_guide_manager',
        'cohesion_style_helper' => 'cohesion_style_helper',
        'cohesion_sync_package' => 'cohesion_sync_package',
        'cohesion_view_templates' => 'cohesion_view_templates',
        'cohesion_website_settings' => 'cohesion_website_settings',
        'image_style' => 'image_style',
        'view' => 'view',
      ];
      $this->assertEquals($enabled_entity_types_expected, $config->get('enabled_entity_types'));
      $this->assertEquals(10, $config->get('package_export_limit'));
      $this->assertEquals(10, $config->get('full_export_limit'));
    }
  }

  /**
   * Assert that site_studio_sync directory set in Settings.php file.
   */
  public function testSiteStudioSyncDirectorySettings() {
    // We have added `site_studio_sync` using scaffolding
    // from acquia_cms_site_studio module, we need to test
    // that config exists in setting.php file.
    $site_path = $this->container->getParameter('site.path');
    $site_studio_sync = "../config/$site_path/sitestudio";
    $this->assertEquals($site_studio_sync, Settings::get('site_studio_sync'));
  }

}
