<?php

namespace Drupal\Tests\acquia_cms_person\ExistingSite;

use Drupal\Tests\acquia_cms_common\Traits\ConfigurationTraits;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the Person content type that ships with Acquia CMS+DAM.
 *
 * @group acquia_cms_person
 * @group acquia_cms_dam
 * @group acquia_cms
 * @group risky
 * @group pr
 * @group push
 */
class PersonImageDamTest extends ExistingSiteBase {

  use ConfigurationTraits;

  // @codingStandardsIgnoreStart
  protected $strictConfigSchema = FALSE;
  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_person',
    'acquia_cms_common',
    'acquia_cms_dam',
  ];

  /**
   * {@inheritdoc}
   */
  public function testPersonDamImageDependencyConfig() {
    $field_config_name = 'field.field.node.person.field_person_image';
    // Check for dependencies config value in field configuration.
    $config_check = $this->configKeyExists($field_config_name, 'dependencies.config');
    $this->assertTrue($config_check);
    // Check for target bundles value in field configuration.
    $config_check = $this->configKeyExists($field_config_name, 'settings.handler_settings.target_bundles');
    $this->assertTrue($config_check);
  }

}
