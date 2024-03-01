<?php

namespace Drupal\Tests\acquia_cms_site_studio\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Site Studio Core Form.
 *
 * @group acquia_cms
 * @group acquia_cms_tour
 * @group acquia_cms_site_studio
 */
class SiteStudioCoreTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_tour',
    'acquia_cms_site_studio',
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
   * Tests the Site Studio Core Form.
   */
  public function testSiteStudioCore() {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser(['access acquia cms tour dashboard']);
    $this->drupalLogin($account);

    // Visit the tour page.
    $this->drupalGet('/admin/tour/dashboard');
    $assert_session->statusCodeEquals(200);

    // Assert that site studio form exists.
    $assert_session->elementExists('css', '.acquia-cms-site-studio-core-form');

    // Assert that Save & Ignore buttons are present on form.
    $assert_session->buttonExists('Save');
    $assert_session->buttonExists('Ignore');
    $assert_session->linkExists('Advanced');

    // Assert that the expected fields show up.
    $assert_session->fieldExists('API key');
    $assert_session->fieldExists('Agency key');
  }

}
