<?php

namespace Drupal\Tests\acquia_cms_tour\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Acquia CMS Tour module's routes.
 *
 * @group acquia_cms
 * @group acquia_cms_tour
 */
class TourAccessRoutesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_tour',
  ];

  /**
   * Tests if dashboard and tour page is accessible with permission.
   */
  public function testTourRoutes() {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser([
      'access acquia cms tour',
    ]);
    $this->drupalLogin($account);

    // Visit the tour dashboard page.
    $this->drupalGet('/admin/tour/dashboard');
    $assert_session->statusCodeEquals(200);

    // Visit the tour page.
    $this->drupalGet('/admin/tour');
    $assert_session->statusCodeEquals(200);
  }

}
