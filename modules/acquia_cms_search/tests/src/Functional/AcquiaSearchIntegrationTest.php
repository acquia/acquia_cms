<?php

namespace Drupal\Tests\acquia_cms_search\Functional;

use Drupal\search_api\Entity\Index;
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
class AcquiaSearchIntegrationTest extends AcquiaSearchConnectionTestBase {

  /**
   * Tests administrative integration with Acquia Search Solr.
   */
  public function testAcquiaSearchIntegration() {
    $this->drupalGet('/admin/config/search/search-api/server/acquia_search_server/edit');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $this->assertSame('database', Index::load('content')->getServerId());
    if ($index = Index::load('acquia_search_index')) {
      $this->assertTrue($index->status());
      $this->assertSame('acquia_search_server', $index->getServerId());
      $this->assertTrue(View::load('acquia_search')->status());
    }
  }

}
