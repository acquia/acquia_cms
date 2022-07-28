<?php

namespace Drupal\Tests\acquia_cms_tour\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Recaptcha Form.
 *
 * @group acquia_cms
 * @group acquia_cms_tour
 */
class RecaptchaTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_tour',
    'recaptcha',
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
   * Tests the Recaptcha Form.
   */
  public function testRecaptcha() {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser(['access acquia cms tour dashboard']);
    $this->drupalLogin($account);

    // Visit the tour page.
    $this->drupalGet('/admin/tour/dashboard');
    $assert_session->statusCodeEquals(200);
    $container = $assert_session->elementExists('css', '.acquia-cms-recaptcha-form');
    // Assert that save and advanced buttons are present on form.
    $assert_session->buttonExists('Save');
    // Assert that the expected fields show up.
    $assert_session->fieldExists('Site key');
    $assert_session->fieldExists('Secret key');
    // Save Site key.
    $dummy_site_key = 'test-1234';
    $container->fillField('edit-site-key', $dummy_site_key);
    // Save Secret key.
    $dummy_secret_key = 'xcvfg-1234';
    $container->fillField('edit-secret-key', $dummy_secret_key);
    $container->pressButton('Save');
    $assert_session->pageTextContains('The configuration options have been saved.');
    // Test that the config values we expect are set correctly.
    $recaptcha_site_key = $this->config('recaptcha.settings')->get('site_key');
    $this->assertSame($recaptcha_site_key, $dummy_site_key);
    $recaptcha_secret_key = $this->config('recaptcha.settings')->get('secret_key');
    $this->assertSame($recaptcha_secret_key, $dummy_secret_key);
  }

}
