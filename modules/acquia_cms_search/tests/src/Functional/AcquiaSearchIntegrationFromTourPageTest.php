<?php

namespace Drupal\Tests\acquia_cms_search\Functional;

use Drupal\search_api\Entity\Index;
use Drupal\Tests\BrowserTestBase;
use Drupal\views\Entity\View;

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
    'acquia_search',
    'acquia_cms_tour',
    'search_api_db',
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
   * Tests Acquia Search Solr integration from tour page.
   */
  public function testAcquiaSearchIntegrationFromTourPage() {
    $assert_session = $this->assertSession();
    $account = $this->drupalCreateUser([
      'administer site configuration',
      'administer search_api',
      'access acquia cms tour dashboard',
    ]);
    $this->drupalLogin($account);
    // Visit the tour page.
    $this->drupalGet('/admin/tour/dashboard');
    $assert_session->statusCodeEquals(200);

    $container = $assert_session->elementExists('css', '.acquia-cms-search-form');
    // Assert that save button is present on form.
    $assert_session->buttonExists('Save');
    // Assert that the expected fields show up.
    $assert_session->fieldExists('Acquia Subscription identifier');
    $assert_session->fieldExists('Acquia Connector key');
    $assert_session->fieldExists('Acquia Application UUID');

    // Save Fields.
    $container->fillField('Acquia Subscription identifier', 'ABCD-12345');
    $container->fillField('Acquia Connector key', $this->randomString());
    $container->fillField('Acquia Application UUID', $this->container->get('uuid')->generate());
    $container->pressButton('Save');

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
