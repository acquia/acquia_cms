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
      $this->assertFalse($config->get('disable_automatic_queueing'));
    }
  }

}
