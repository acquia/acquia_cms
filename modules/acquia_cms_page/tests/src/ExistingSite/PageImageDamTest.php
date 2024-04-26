<?php

namespace Drupal\Tests\acquia_cms_page\ExistingSite;

use Drupal\Tests\acquia_cms_common\Traits\ConfigurationTraits;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the Page content type that ships with Acquia CMS + DAM.
 *
 * @group acquia_cms_page
 * @group acquia_cms_dam
 * @group acquia_cms
 * @group risky
 * @group pr
 * @group push
 */
class PageImageDamTest extends ExistingSiteBase {

  use ConfigurationTraits;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_page',
    'acquia_cms_common',
    'acquia_cms_dam',
  ];

  /**
   * {@inheritdoc}
   */
  public function testPageDamImageDependencyConfig() {
    $field_config_name = 'field.field.node.page.field_page_image';
    // Check for dependencies config value in field configuration.
    $config_check = $this->configKeyExists($field_config_name, 'dependencies.config');
    $this->assertTrue($config_check);
    // Check for target bundles value in field configuration.
    $config_check = $this->configKeyExists($field_config_name, 'settings.handler_settings.target_bundles');
    $this->assertTrue($config_check);
  }

}
