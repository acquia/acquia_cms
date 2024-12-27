<?php

namespace Drupal\Tests\acquia_cms_tour\Functional;

use Drupal\geocoder\Entity\GeocoderProvider;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Acquia CMS Tour module's integration with Geocoder & Google Maps.
 *
 * @group acquia_cms
 * @group acquia_cms_tour
 * @group risky
 */
class GeocoderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_tour',
    'geocoder',
  ];

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->moduleHandler = $this->container->get("module_handler");
  }

  /**
   * Tests that the Google Maps API key can be set on the tour page.
   */
  public function testGeocoderGoogleMaps() {
    $assert_session = $this->assertSession();

    // Create an administrator account with all permissions.
    $admin_user = $this->drupalCreateUser([], NULL, TRUE);

    // Log in the administrator account.
    $this->drupalLogin($admin_user);

    $this->drupalGet('/admin/config/system/geocoder/geocoder-provider');
    $assert_session->statusCodeEquals(200);

    // Select googlemaps option from dropdown.
    $assert_session->elementExists('css', '[data-drupal-selector="edit-geocoder-provider"]')
      ->selectOption('googlemaps');
    $assert_session->buttonExists('Add')->press();
    $assert_session->pageTextContains('Add a Geocoder provider');

    $container = $assert_session->elementExists('css', '[data-drupal-selector="geocoder-provider-add-form"]');
    $container->fillField('edit-label', 'GoogleMaps');
    $container->fillField('edit-id', 'googlemaps');
    $container->fillField('edit-apikey', 'oldkey12345');
    $container->pressButton('Save');
    $assert_session->pageTextContains('Created new geocoder provider');

    // Create user account with 'access acquia cms tour dashboard' permission.
    $account = $this->drupalCreateUser(['access acquia cms tour dashboard']);
    $this->drupalLogin($account);

    // Visit the tour page.
    $this->drupalGet('/admin/tour/dashboard');
    $assert_session->statusCodeEquals(200);

    $container = $assert_session->elementExists('css', '[data-drupal-selector="edit-geocoder"]');
    $assert_session->fieldValueEquals('maps_api_key', 'oldkey12345', $container);
    $container->fillField('maps_api_key', '');
    $container->pressButton('Save');
    $assert_session->pageTextContains('Maps API key field is required.');

    // Save a dummmy API key.
    $dummy_key = 'keykeykey123';
    $container->fillField('edit-maps-api-key', $dummy_key);
    $container->pressButton('Save');
    $assert_session->pageTextContains('The Google Maps API key has been set.');

    // Test that the config values we expect are set correctly for Site Studio.
    if ($this->moduleHandler->moduleExists('cohesion')) {
      $cohesion_map_key = $this->config('cohesion.settings')->get('google_map_api_key');
      $this->assertSame($cohesion_map_key, $dummy_key);
    }

    $configuration = GeocoderProvider::load('googlemaps')->get('configuration');
    $this->assertSame($configuration['apiKey'], $dummy_key);
  }

}
