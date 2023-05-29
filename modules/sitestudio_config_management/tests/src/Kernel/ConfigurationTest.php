<?php

namespace Drupal\Tests\sitestudio_config_management\Kernel;

use Drupal\KernelTests\KernelTestBase;

class ConfigurationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // We are using drupal's module_installer service to install the modules.
    // Because KernelTests uses enableModules() function to install the modules
    // and this doesn't import Drupal configurations.
    $this->container->get("module_installer")->install(['sitestudio_config_management']);
  }

  /**
   * Tests the configurations provided by sitestudio_config_management module.
   *
   * @dataProvider providerConfigurations
   */
  public function testConfiguration(string $config, array $data): void {
    $configSplitData = $this->config($config)->getRawData();
    $this->assertNotEmpty($configSplitData, sprintf("Configuration '%s' doesn't exist or contains no data.", $config));
    foreach ($data as $key => $value) {
      $this->assertArrayHasKey($key, $configSplitData, sprintf("Configuration '%s' doesn't contain key: '%s'.", $config, $key));
      $this->assertSame($configSplitData[$key], $value, sprintf("Configuration '%s' for key: '%s' doesn't matches.", $config, $key));
    }
  }

  /**
   * Data Provider for configurations to tests.
   */
  public function providerConfigurations(): array {
    return [
      [
        "config_split.config_split.site_studio",
        [
          "status" => TRUE,
          "dependencies" => [
            "enforced" => [
              "module" => ["sitestudio_config_management"],
            ],
          ],
          "id" => "site_studio",
          "label" => "Site Studio",
          "description" => "Split site studio package.",
          "weight" => 0,
          "storage" => "database",
          "folder" => "sitestudio",
          "complete_list" => ["cohesion_*"],
        ],
      ],
      [
        "cohesion.sync.settings",
        [
          "enabled_entity_types" => [
            "cohesion_base_styles" => "cohesion_base_styles",
            "cohesion_color" => "cohesion_color",
            "cohesion_component" => "cohesion_component",
            "cohesion_component_category" => "cohesion_component_category",
            "cohesion_content_templates" => "cohesion_content_templates",
            "cohesion_custom_style" => "cohesion_custom_style",
            "cohesion_font_library" => "cohesion_font_library",
            "cohesion_font_stack" => "cohesion_font_stack",
            "cohesion_helper" => "cohesion_helper",
            "cohesion_helper_category" => "cohesion_helper_category",
            "cohesion_icon_library" => "cohesion_icon_library",
            "cohesion_master_templates" => "cohesion_master_templates",
            "cohesion_menu_templates" => "cohesion_menu_templates",
            "cohesion_scss_variable" => "cohesion_scss_variable",
            "cohesion_style_guide" => "cohesion_style_guide",
            "cohesion_style_guide_manager" => "cohesion_style_guide_manager",
            "cohesion_style_helper" => "cohesion_style_helper",
            "cohesion_sync_package" => "cohesion_sync_package",
            "cohesion_view_templates" => "cohesion_view_templates",
            "cohesion_website_settings" => "cohesion_website_settings",
          ],
          "package_export_limit" => 10,
          "full_export_limit" => 10,
        ],
      ],
    ];
  }

}
