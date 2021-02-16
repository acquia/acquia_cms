<?php

namespace Drupal\Tests\acquia_cms_tour\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Site Studio Core Form.
 *
 * @group acquia_cms
 * @group acquia_cms_tour
 */
class SiteStudioCore extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_tour',
    'cohesion',
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
   * Tests the Site Studio Core Form.
   */
  public function testSiteStudioCore() {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser(['access acquia cms tour dashboard']);
    $this->drupalLogin($account);

    // Visit the tour page.
    $this->drupalGet('/admin/tour/dashboard');
    $assert_session->statusCodeEquals(200);
    $container = $assert_session->elementExists('css', '.acquia-cms-site-studio-core-form');
    // Assert that save and advanced buttons are present on form.
    $assert_session->buttonExists('Save');
    // Assert that the expected fields show up.
    $assert_session->fieldExists('API key');
    $assert_session->fieldExists('Agency key');
    // Save API key.
    $dummy_api_key = 'test-key-13fdnj32';
    $container->fillField('edit-api-key', $dummy_api_key);
    // Save Agency key key.
    $dummy_agency_key = 'test-agency-13fdnj32';
    $container->fillField('edit-agency-key', $dummy_agency_key);
    $container->pressButton('Save');
    $assert_session->pageTextContains('The configuration options have been saved.');
    // Test that the config values we expect are set correctly.
    $cohesion_api_key = $this->config('cohesion.settings')->get('api_key');
    $this->assertSame($cohesion_api_key, $dummy_api_key);
    $cohesion_agency_key = $this->config('cohesion.settings')->get('organization_key');
    $this->assertSame($cohesion_agency_key, $dummy_agency_key);
  }

}
