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
   * Returns a config object by name.
   *
   * @param string $name
   *   The name of the config object to return.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The config object, read-only to discourage this test from making any
   *   changes.
   */
  private function config(string $name): ImmutableConfig {
    return $this->container->get('config.factory')->get($name);
  }

  /**
   * Assert that node revision delete default configs are available.
   */
  public function testNodeRevisionDeleteConfig() {
    // Check that node revision delete default configs are in place.
    $config = $this->config('node_revision_delete.settings');
    if ($config) {
      // Total revisions set for delete.
      $this->assertTrue($config->get('defaults.amount.status'));
      $this->assertEquals(50, $config->get('defaults.amount.settings.amount'));
      // Total duration i.e 12 months is minimum to keep revisions.
      $this->assertTrue($config->get('defaults.created.status'));
      $this->assertEquals(12, $config->get('defaults.created.settings.age'));
      $this->assertTrue($config->get('defaults.drafts.status'));
      $this->assertEquals(12, $config->get('defaults.drafts.settings.age'));
      // By default it is set to FALSE.
      $this->assertFalse($config->get('disable_automatic_queueing'));
    }
  }

  /**
   * Assert that node revision delete configs available for Page content type.
   */
  public function testContentTypeConfig() {
    // Check node revision delete configs for Page content type are in palce.
    $config = $this->config('node.type.page');
    if ($config) {
      $this->assertTrue($config->get('third_party_settings.node_revision_delete.amount.status'));
      // Total revisions for Page content type.
      $this->assertSame(30, $config->get('third_party_settings.node_revision_delete.amount.settings.amount'));
      // Keep revisions for active and drafts contents.
      $this->assertSame(0, $config->get('third_party_settings.node_revision_delete.created.settings.age'));
      $this->assertSame(0, $config->get('third_party_settings.node_revision_delete.drafts.settings.age'));
      $this->assertSame(0, $config->get('third_party_settings.node_revision_delete.drafts_only.settings.age'));
    }
  }

}
