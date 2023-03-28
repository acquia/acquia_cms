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
  public function testAcquiaSearch(): void {
    $assert = $this->assertSession();

    $account = $this->drupalCreateUser(['access acquia cms tour dashboard']);
    $this->drupalLogin($account);

    // Visit the tour page.
    $this->drupalGet('/admin/tour/dashboard');
    $assert->statusCodeEquals(200);
    $container = $assert->elementExists('css', '.acquia-cms-search-form');
    // Assert that save and advanced buttons are present on form.
    $assert->buttonExists('Save');
    // Assert that the expected fields show up.
    $assert->fieldExists('Acquia Subscription identifier');
    $assert->fieldExists('Acquia Search API hostname');
    $assert->fieldExists('Acquia Application UUID');
    // Save Subscription identifier.
    $dummyIdentifier = getenv('CONNECTOR_ID') ?: 'ABCD-12345';
    $container->fillField('edit-identifier', $dummyIdentifier);
    // Save Search API hostname.
    $dummyHostname = 'https://api.sr-prod02.acquia.com';
    $container->fillField('edit-api-host', $dummyHostname);
    // Save Application UUID.
    $dummyUuid = getenv('SEARCH_UUID');
    $container->fillField('edit-uuid', $dummyUuid);
    $container->pressButton('Save');
    $assert->pageTextContains('The configuration options have been saved.');
    // Test that the config values we expect are set correctly.
    $state = $this->container->get('state');
    $solrIdentifier = $state->get('acquia_connector.identifier');
    $this->assertSame($solrIdentifier, $dummyIdentifier);
    $solrApiHost = $this->config('acquia_search.settings')->get('api_host');
    $this->assertSame($solrApiHost, $dummyHostname);
    $solrUuid = $state->get('acquia_connector.application_uuid');
    $this->assertSame($solrUuid, $dummyUuid);
  }

}
