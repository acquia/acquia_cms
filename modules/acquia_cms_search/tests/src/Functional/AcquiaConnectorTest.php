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
   * Tests the Acquia CMS Connector form.
   */
  public function testAcquiaConnector() {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser(['access acquia cms tour dashboard']);
    $this->drupalLogin($account);

    // Visit the tour page.
    $this->drupalGet('/admin/tour/dashboard');
    $assert_session->statusCodeEquals(200);
    // Assert that the expected fields show up.
    $assert_session->fieldExists('Name');
    // Assert that save and advanced buttons are present on form.
    $assert_session->buttonExists('Save');
  }

}
