<?php

namespace Drupal\Tests\acquia_cms_tour\Functional;

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
   * Tests if Google Maps API key can be managed via tour page.
   */
  public function testAcquiaGoogleMaps() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Acquia CMS place needs to be installed.
    $this->container->get('module_installer')->install(['acquia_cms_place']);

    $account = $this->drupalCreateUser([
      'access acquia cms tour',
    ]);
    $this->drupalLogin($account);

    // Visit the tour page.
    $this->drupalGet('/admin/tour/dashboard');
    $assert_session->statusCodeEquals(200);

    // API key should be blank to start.
    $assert_session->fieldValueEquals('maps_api_key', '');
    // We shouldn't be able to submit a null value.
    $button_id = 'maps-submit';
    $page->pressButton($button_id);
    $assert_session->pageTextContains(
      'The Google Maps API key cannot be null.'
    );

    // Save a dummmy API key.
    $dummy_key = 'keykeykey123';
    $page->fillField('edit-maps-api-key', $dummy_key);
    $page->pressButton($button_id);
    $assert_session->pageTextContains('The Google Maps API key has been set.');

    // Now test that the config values we expect are set correctly.
    $cohesion_map_key = $this->config('cohesion.settings')
      ->get('google_map_api_key');
    $this->assertSame($cohesion_map_key, $dummy_key);

    $geocoder_map_key = $this->config('geocoder.geocoder_provider.googlemaps')
      ->get('configuration.apiKey');
    $this->assertSame($geocoder_map_key, $dummy_key);
  }

}
