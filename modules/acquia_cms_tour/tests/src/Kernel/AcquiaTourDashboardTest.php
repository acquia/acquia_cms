<?php

namespace Drupal\Tests\acquia_cms_tour\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Acquia CMS Tour module's dashboard implementation using plugin system.
 *
 * @group acquia_cms
 * @group acquia_cms_tour
 * @group low_risk
 * @group pr
 * @group push
 */
class AcquiaTourDashboardTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_tour',
  ];

  /**
   * Tests AcquiaCMSTour plugins and make sure they are sorted per weights.
   */
  public function testAcquiaCmsTourPlugin() {
    $expected_plugin_order = [
      'geocoder',
      'google_analytics',
      'google_tag',
      'recaptcha',
    ];
    $plugins = $this->container->get('plugin.manager.acquia_cms_tour')->getTourManagerPlugin();
    $this->assertEquals(array_keys($plugins), $expected_plugin_order);
  }

}
