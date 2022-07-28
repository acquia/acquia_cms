<?php

namespace Drupal\Tests\acquia_cms_search\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Acquia Search Solr Form.
 *
 * @group acquia_cms
 * @group acquia_cms_tour
 * @group acquia_cms_search
 */
class AcquiaSearchTest extends BrowserTestBase {


  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_tour',
    'acquia_cms_search',
    'acquia_search',
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
   * Tests the Acquia Search Solr Form.
   */
  public function testAcquiaSearch() {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser(['access acquia cms tour dashboard']);
    $this->drupalLogin($account);

    // Visit the tour page.
    $this->drupalGet('/admin/tour/dashboard');
    $assert_session->statusCodeEquals(200);
    $container = $assert_session->elementExists('css', '.acquia-cms-search-form');
    // Assert that save and advanced buttons are present on form.
    $assert_session->buttonExists('Save');
    // Assert that the expected fields show up.
    $assert_session->fieldExists('Acquia Subscription identifier');
    $assert_session->fieldExists('Acquia Search API hostname');
    $assert_session->fieldExists('Acquia Application UUID');
    // Save Subscription identifier.
    $dummy_identifier = getenv('CONNECTOR_ID');
    $container->fillField('edit-identifier', $dummy_identifier);
    // Save Search API hostname.
    $dummy_hostname = 'https://api.sr-prod02.acquia.com';
    $container->fillField('edit-api-host', $dummy_hostname);
    // Save Application UUID.
    $dummy_uuid = getenv('SEARCH_UUID');
    $container->fillField('edit-uuid', $dummy_uuid);
    $container->pressButton('Save');
    $assert_session->pageTextContains('The configuration options have been saved.');
    // Test that the config values we expect are set correctly.
    $state = $this->container->get('state');
    $solr_identifier = $state->get('acquia_search.identifier');
    $this->assertSame($solr_identifier, $dummy_identifier);
    $solr_api_host = $this->config('acquia_search.settings')->get('api_host');
    $this->assertSame($solr_api_host, $dummy_hostname);
    $solr_uuid = $state->get('acquia_search.uuid');
    $this->assertSame($solr_uuid, $dummy_uuid);
  }

}
