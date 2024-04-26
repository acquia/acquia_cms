<?php

namespace Drupal\Tests\acquia_cms_article\ExistingSite;

use Drupal\Tests\acquia_cms_common\Traits\ConfigurationTraits;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the Article Image that ships with Acquia CMS + DAM.
 *
 * @group acquia_cms_article
 * @group acquia_cms_dam
 * @group acquia_cms
 * @group risky
 * @group pr
 * @group push
 */
class ArticleImageDamTest extends ExistingSiteBase {

  use ConfigurationTraits;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_article',
    'acquia_cms_common',
    'acquia_cms_dam',
  ];

  /**
   * {@inheritdoc}
   */
  public function testArticleDamImageDependencyConfig() {
    $field_config_name = 'field.field.node.article.field_article_image';
    // Check for dependencies config value in field configuration.
    $config_check = $this->configKeyExists($field_config_name, 'dependencies.config');
    $this->assertTrue($config_check);
    // Check for target bundles value in field configuration.
    $config_check = $this->configKeyExists($field_config_name, 'settings.handler_settings.target_bundles');
    $this->assertTrue($config_check);
  }

}
