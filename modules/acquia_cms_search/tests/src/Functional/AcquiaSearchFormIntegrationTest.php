<?php

namespace Drupal\Tests\acquia_cms_search\Functional;

use Drupal\search_api\Entity\Index;
use Drupal\Tests\BrowserTestBase;
use Drupal\views\Entity\View;

/**
 * Tests integration with Acquia Search Solr.
 *
 * @group acquia_cms
 * @group acquia_cms_search
 * @group low_risk
 * @group pr
 * @group push
 */
class AcquiaSearchFormIntegrationTest extends BrowserTestBase {

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
    'acquia_search',
    'acquia_cms_tour',
    'cohesion',
    'search_api_db',
  ];

  /**
   * Tests administrative integration with Acquia Search Solr.
   */
  public function testAcquiaSearchFormIntegration() {
    $this->assertSame('database', Index::load('content')->getServerId());

    $index = Index::load('acquia_search_index');
    $this->assertTrue($index->status());
    $this->assertSame('acquia_search_server', $index->getServerId());

    $this->assertTrue(View::load('acquia_search')->status());

    $account = $this->drupalCreateUser([
      'administer site configuration',
      'administer search_api',
      'access acquia cms tour dashboard',
    ]);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/tour/dashboard');

    $page = $this->getSession()->getPage();
    $page->fillField('Acquia Subscription identifier', 'ABCD-12345');
    $page->fillField('Acquia Connector key', $this->randomString());
    $page->fillField('Acquia Application UUID', $this->container->get('uuid')->generate());
    $page->pressButton('edit-submit--3');

    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('The configuration options have been saved.');

    // Our index should be using the Solr server, whereas the one that ships
    // with Acquia Search Solr should be disabled, along with any views that are
    // using it.
    $this->assertSame('acquia_search_server', Index::load('content')->getServerId());
    $index = Index::load('acquia_search_index');
    $this->assertFalse($index->status());
    $this->assertNull($index->getServerId());
    $this->assertFalse(View::load('acquia_search')->status());
  }

}
