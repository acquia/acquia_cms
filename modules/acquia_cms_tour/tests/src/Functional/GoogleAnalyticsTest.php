<?php

namespace Drupal\Tests\acquia_cms_tour\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Google Analytics Form.
 *
 * @group acquia_cms
 * @group acquia_cms_tour
 */
class GoogleAnalyticsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_tour',
    'google_analytics',
  ];

  /**
   * Tests the Google Analytics Form.
   */
  public function testGoogleAnalytics() {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser(['access acquia cms tour dashboard']);
    $this->drupalLogin($account);

    // Visit the tour page.
    $this->drupalGet('/admin/tour/dashboard');
    $assert_session->statusCodeEquals(200);
    $container = $assert_session->elementExists('css', '.acquia-cms-google-analytics-form');
    // Assert that save and advanced buttons are present on form.
    $assert_session->buttonExists('Save');
    // Assert that the expected fields show up.
    $assert_session->fieldExists('Web Property ID');
    // Save Web Property ID.
    $dummy_web_property_id = 'UA-334567-6789078';
    $container->fillField('edit-web-property-id', $dummy_web_property_id);
    $container->pressButton('Save');
    $this->assertTrue($this->isValidPropertyId($dummy_web_property_id));
    $assert_session->pageTextContains('The configuration options have been saved.');
    // Test that the config values we expect are set correctly.
    $google_tag_prop_id = $this->config('google_analytics.settings')->get('account');
    $this->assertSame($google_tag_prop_id, $dummy_web_property_id);
  }

  /**
   * Function to check GA web property id.
   */
  public function isValidPropertyId($dummy_web_property_id) {
    if (!preg_match('/^UA-\d+-\d+$/', $dummy_web_property_id)) {
      return FALSE;
    }
    return TRUE;
  }

}
