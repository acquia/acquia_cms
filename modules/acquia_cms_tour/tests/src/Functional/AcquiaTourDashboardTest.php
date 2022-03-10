<?php

namespace Drupal\Tests\acquia_cms_tour\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Acquia CMS Tour module's dashboard implementation using plugin system.
 *
 * @group acquia_cms
 * @group acquia_cms_tour
 * @group risky
 */
class AcquiaTourDashboardTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_tour',
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
   * Tests AcquiaCMSTour plugins and make sure they are sorted per weights.
   */
  public function testAcquiaCmsTourPlugin() {
    $expected_plugin_order = [
      'acquia_telemetry',
      'geocoder',
      'google_analytics',
      'google_tag',
      'recaptcha',
    ];
    $plugins = $this->container->get('plugin.manager.acquia_cms_tour')->getTourManagerPlugin();
    $this->assertEquals(array_keys($plugins), $expected_plugin_order);
  }

}
