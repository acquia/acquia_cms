<?php

namespace Drupal\Tests\acquia_cms_search\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Acquia CMS Connector form.
 *
 * @group acquia_cms
 * @group acquia_cms_tour
 * @group acquia_cms_search
 */
class AcquiaConnectorTest extends BrowserTestBase {

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
    'acquia_connector',
  ];

  /**
   * Tests the Acquia CMS Connector form.
   */
  public function testAcquiaConnector() {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser(['access acquia cms tour dashboard']);
    $this->drupalLogin($account);

    // Visit the tour page.
    $this->drupalGet('/admin/tour/dashboard');
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldExists('Name');
    // Assert that save and advanced buttons are present on form.
    $assert_session->buttonExists('Save');
  }

}
