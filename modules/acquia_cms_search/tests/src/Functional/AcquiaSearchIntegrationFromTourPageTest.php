<?php

namespace Drupal\Tests\acquia_cms_search\Functional;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\search_api\Entity\Index;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests integration with Acquia Search Solr.
 *
 * @group acquia_cms
 * @group acquia_cms_tour
 * @group acquia_cms_search
 * @group low_risk
 * @group pr
 * @group push
 */
class AcquiaSearchIntegrationFromTourPageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_search',
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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Check active subscription of acquia environment.
    if (!AcquiaDrupalEnvironmentDetector::getAhApplicationUuid()) {
      $this->markTestSkipped('This test can only run when acquia application uuid is set.');
    }
    parent::setUp();
    $account = $this->drupalCreateUser([
      'administer site configuration',
      'administer search_api',
      'access acquia cms tour dashboard',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Tests Acquia Search Solr integration from tour page.
   */
  public function testAcquiaSearchIntegration() {
    $assert = $this->assertSession();
    // By default Search server index id is database.
    $this->drupalGet('admin/config/search/search-api');
    $assert->statusCodeEquals(200);
    $assert->linkExists('Database Search Server');

    $index = Index::load('content');
    $this->assertTrue($index->status());
    $this->assertSame('database', $index->getServerId());
    // Acquia search server is disabled.
    $this->drupalGet('admin/config/search/search-api/server/acquia_search_server/edit');
    $assert->fieldValueEquals('name', 'Acquia Search API Solr server');
    $assert->checkboxNotChecked('status');
    // Visit the tour page.
    $this->drupalGet('/admin/tour/dashboard');
    $assert->statusCodeEquals(200);

    $formElement = $assert->elementExists('css', '.acquia-cms-search-form');
    $assert->pageTextContains("Provides integration between your Drupal site and Acquia's hosted search service.");
    // Assert that the expected fields show up.
    $assert->fieldExists('Acquia Subscription identifier');
    $assert->fieldExists('Acquia Connector key');
    $assert->fieldExists('Acquia Search API hostname');
    $assert->fieldExists('Acquia Application UUID');
    // Assert that save button is present on form.
    $assert->buttonExists('Save');
    // Acquia subscription identifier.
    $connectorId = getenv('CONNECTOR_ID') ?: 'ABCD-12345';
    // Acquia connector key.
    $connectorKey = getenv('CONNECTOR_KEY') ?: $this->randomString();
    // Acquia application uuid.
    $applicationUuid = getenv('AH_APPLICATION_UUID') ?: $this->container->get('uuid')->generate();
    // Save acquia search form.
    $formElement->fillField('Acquia Subscription identifier', $connectorId);
    $formElement->fillField('Acquia Connector key', $connectorKey);
    $formElement->fillField('Acquia Application UUID', $applicationUuid);
    $formElement->pressButton('Save');
    $assert->pageTextContains('The configuration options have been saved.');

    $assert->pageTextContains('The Content search index is now using the Acquia Search API Solr server server. All content will be reindexed.');

    // Validate Acquia search solr.
    $this->drupalGet('admin/config/search/search-api');
    $assert->statusCodeEquals(200);
    $assert->linkExists('Acquia Search API Solr server');
    $this->drupalGet('admin/config/search/search-api/server/acquia_search_server');
    $assert->statusCodeEquals(200);
    $this->drupalGet('admin/config/search/search-api/index/content');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Connection managed by Acquia Search Solr module.');
    $index = Index::load('content');
    $this->assertTrue($index->status());
    $this->assertSame('acquia_search_server', $index->getServerId());
  }

}
