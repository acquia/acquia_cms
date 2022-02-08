<?php

namespace Drupal\Tests\acquia_cms_site_studio\ExistingSite;

use Drupal\Core\Config\ImmutableConfig;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests config values that are set at install.
 *
 * @group acquia_cms
 * @group profile
 * @group risky
 */
class NodeRevisionDeleteConfigTest extends ExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_article',
    'acquia_cms_event',
    'acquia_cms_page',
    'acquia_cms_person',
    'acquia_cms_place',
    'acquia_cms_site_studio',
    'node',
    'node_revision_delete',
  ];

  /**
   * Returns a config object by name.
   *
   * @param string $name
   *   The name of the config object to return.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The config object, read-only to discourage this test from making any
   *   changes.
   */
  private function config(string $name) : ImmutableConfig {
    return $this->container->get('config.factory')->get($name);
  }

  /**
   * Assert that node revision delete default configs are available.
   */
  public function testNodeRevisionDeleteConfig() {
    // Check that node revision delete default configs are in place.
    $config = $this->config('node_revision_delete.settings');
    if ($config) {
      $this->assertEquals(50, $config->get('node_revision_delete_cron'));
      $this->assertEquals(604800, $config->get('node_revision_delete_time'));
    }
  }

  /**
   * Assert that node revision delete configs available for Page content type.
   */
  public function testContentTypeConfig() {
    // Check node revision delete configs for Page content type are in palce.
    $config = $this->config('node.type.page');
    if ($config) {
      $this->assertSame(30, $config->get('third_party_settings.node_revision_delete.minimum_revisions_to_keep'));
      $this->assertSame(0, $config->get('third_party_settings.node_revision_delete.minimum_age_to_delete'));
      $this->assertSame(0, $config->get('third_party_settings.node_revision_delete.when_to_delete'));
    }
  }

}
