<?php

namespace Drupal\Tests\acquia_cms_search\Functional;

use Drupal\search_api\Entity\Index;
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
class AcquiaSearchIntegrationFromTourPageTest extends AcquiaSearchConnectionTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_tour',
  ];

  /**
   * {@inheritdoc}
   */
  protected $permissions = [
    'access acquia cms tour dashboard',
  ];

  /**
   * Tests Acquia Search Solr integration from tour page.
   */
  public function testAcquiaSearchIntegrationFromTourPage() {
    $assert = $this->assertSession();
    // Visit the tour page.
    $this->drupalGet('/admin/tour/dashboard');
    $assert->statusCodeEquals(200);

    $container = $assert->elementExists('css', '.acquia-cms-search-form');
    // Assert that save button is present on form.
    $assert->buttonExists('Save');
    // Assert that the expected fields show up.
    $assert->fieldExists('Acquia Subscription identifier');
    $assert->fieldExists('Acquia Connector key');
    $assert->fieldExists('Acquia Application UUID');

    // Save Fields.
    // @todo to check with environment variable and
    // then check acquia search api server is working.
    $container->fillField('Acquia Subscription identifier', 'ABCD-12345');
    $container->fillField('Acquia Connector key', $this->randomString());
    $container->fillField('Acquia Application UUID', $this->container->get('uuid')->generate());
    $container->pressButton('Save');

    $assert->pageTextContains('The configuration options have been saved.');

    // Our index should be using the Solr server, whereas the one that ships
    // with Acquia Search Solr should be disabled, along with any views that are
    // using it.
    $this->assertSame('acquia_search_server', Index::load('content')
      ->getServerId());
    if ($index = Index::load('acquia_search_index')) {
      $this->assertFalse($index->status());
      $this->assertNull($index->getServerId());
      $this->assertFalse(View::load('acquia_search')->status());
    }

  }

}
