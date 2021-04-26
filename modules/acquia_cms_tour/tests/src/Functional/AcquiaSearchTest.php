<?php

namespace Drupal\Tests\acquia_cms_tour\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Acquia Search Solr Form.
 *
 * @group acquia_cms
 * @group acquia_cms_tour
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
    $container = $assert_session->elementExists('css', '.acquia-cms-solr-search-form');
    // Assert that save and advanced buttons are present on form.
    $assert_session->buttonExists('Save');
    $assert_session->elementExists('css', '.advanced-button');
    // Assert that the expected fields show up.
    $assert_session->fieldExists('Acquia Subscription identifier');
    $assert_session->fieldExists('Acquia Search API hostname');
    $assert_session->fieldExists('Acquia Application UUID');
    $assert_session->fieldExists('Acquia Connector key');
    $assert_session->fieldExists('Acquia API key');
    $assert_session->fieldExists('Acquia API secret');
    // Save Subscription identifier.
    $dummy_identifier = getenv('CONNECTOR_ID');
    $container->fillField('edit-identifier', $dummy_identifier);
    // Save Search API hostname.
    $dummy_hostname = 'https://api.sr-prod02.acquia.com';
    $container->fillField('edit-api-host', $dummy_hostname);
    // Save Application UUID.
    $dummy_uuid = getenv('SEARCH_UUID');
    $container->fillField('edit-uuid', $dummy_uuid);
    // Save Acquia Connector key, API key & secret.
    $dummy_connector_key = getenv('CONNECTOR_KEY');
    $container->fillField('edit-api-key', $dummy_connector_key);
    $dummy_cloud_api_key = getenv('CLOUD_API_KEY');
    $container->fillField('edit-cloud-api-key', $dummy_cloud_api_key);
    $dummy_cloud_api_secret = getenv('CLOUD_API_SECRET');
    $container->fillField('edit-cloud-api-secret', $dummy_cloud_api_secret);

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
    $solr_api_key = $state->get('acquia_search.api_key');
    $this->assertSame($dummy_connector_key, $solr_api_key);
    $cloud_api_key = $state->get('acquia_search.cloud_api_key');
    $this->assertSame($dummy_cloud_api_key, $cloud_api_key);
    $cloud_api_secret = $state->get('acquia_search.cloud_api_secret');
    $this->assertSame($dummy_cloud_api_secret, $cloud_api_secret);
  }

}
