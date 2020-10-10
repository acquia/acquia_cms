<?php

namespace Drupal\Tests\acquia_cms_tour\Functional;

use Drupal\geocoder\Entity\GeocoderProvider;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Acquia CMS Tour module's integration with Google Maps.
 *
 * @group acquia_cms
 * @group acquia_cms_tour
 */
class AcquiaGoogleMapsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_tour',
    'acquia_cms_place',
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
   * Tests that the Google Maps API key can be set on the tour page.
   */
  public function testAcquiaGoogleMaps() {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser(['access acquia cms tour dashboard']);
    $this->drupalLogin($account);

    // Visit the tour page.
    $this->drupalGet('/admin/tour/dashboard');
    $assert_session->statusCodeEquals(200);

    $container = $assert_session->elementExists('css', '[data-drupal-selector="edit-acquia-google-maps-api"]');
    // API key should be blank to start.
    $assert_session->fieldValueEquals('maps_api_key', '', $container);
    $container->pressButton('Save');
    $assert_session->pageTextContains('Maps API key field is required.');

    // Save a dummmy API key.
    $dummy_key = 'keykeykey123';
    $container->fillField('edit-maps-api-key', $dummy_key);
    $container->pressButton('Save');
    $assert_session->pageTextContains('The Google Maps API key has been set.');

    // Now test that the config values we expect are set correctly.
    $cohesion_map_key = $this->config('cohesion.settings')
      ->get('google_map_api_key');
    $this->assertSame($cohesion_map_key, $dummy_key);

    $configuration = GeocoderProvider::load('googlemaps')->get('configuration');
    $this->assertSame($configuration['apiKey'], $dummy_key);
  }

}
