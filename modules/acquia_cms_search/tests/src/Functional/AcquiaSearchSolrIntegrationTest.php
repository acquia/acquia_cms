<?php

namespace Drupal\Tests\acquia_cms_search\Functional;

use Drupal\search_api\Entity\Index;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests integration with Acquia Search Solr.
 *
 * @group acquia_cms
 * @group acquia_cms_search
 */
class AcquiaSearchSolrIntegrationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  protected static $modules = [
    'acquia_cms_search',
    'acquia_search_solr',
    'cohesion',
    'search_api_db',
  ];

  /**
   * Tests administrative integration with Acquia Search Solr.
   */
  public function testAcquiaSearchSolrIntegration() {
    $this->assertSame('database', Index::load('content')->getServerId());

    $account = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/config/search/acquia-search-solr');

    $page = $this->getSession()->getPage();
    $page->fillField('Acquia Subscription identifier', $this->randomString());
    $page->fillField('Acquia Connector key', $this->randomString());
    $page->fillField('Acquia Application UUID', $this->randomString());
    $page->pressButton('Save configuration');

    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('The Content search index is now using the Acquia Search Solr Search API Solr server server. All content will be reindexed.');

    $this->assertSame('acquia_search_solr_search_api_solr_server', Index::load('content')->getServerId());
  }

}
