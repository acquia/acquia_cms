<?php

namespace Drupal\Tests\acquia_cms_tour\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Google Tag Manager Form.
 *
 * @group acquia_cms
 * @group acquia_cms_tour
 */
class GoogleTagManager extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_tour',
    'google_tag',
  ];

  /**
   * Tests the Google Tag Manager Form.
   */
  public function testGoogleTagManager() {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser(['access acquia cms tour dashboard']);
    $this->drupalLogin($account);

    // Visit the tour page.
    $this->drupalGet('/admin/tour/dashboard');
    $assert_session->statusCodeEquals(200);
    $container = $assert_session->elementExists('css', '.acquia-cms-google-tag-manager-form');
    // Assert that save and advanced buttons are present on form.
    $assert_session->buttonExists('Save');
    // Assert that the expected fields show up.
    $assert_session->fieldExists('Snippet parent URI');
    // Save Snippet parent URI.
    $dummy_uri = 'public://tempdir';
    $container->fillField('edit-snippet-parent-uri', $dummy_uri);
    $container->pressButton('Save');
    $assert_session->pageTextContains('The configuration options have been saved.');
    // Test that the config values we expect are set correctly.
    $google_tag_uri = $this->config('google_tag.settings')->get('uri');
    $this->assertSame($google_tag_uri, $dummy_uri);
  }

}
