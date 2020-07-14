<?php

namespace Drupal\Tests\acquia_cms_tour\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests tour page permissions for the user roles included with Acquia CMS.
 *
 * @group acquia_cms_tour
 * @group acquia_cms
 */
class GoogleAnalyticsAndGoogleTagManagerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_tour',
    'google_analytics',
    'google_tag',
    'toolbar',
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
   * Tests Google Analytics & Google Tag Manager integration.
   */
  public function testGoogleAnalyticsAndTagManagerIntegration() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser([
      'access acquia cms tour',
      'administer google analytics',
      'administer google tag manager',
      'access toolbar',
    ]);
    $this->drupalLogin($account);

    // Visit the tour page.
    $this->drupalGet('/admin/tour');
    $assert_session->statusCodeEquals(200);

    // Assert that a link to the Google Analytics configuration page appears.
    $assert_session->linkExists('Google Analytics');

    // Assert that a status message appears warning the user that the API key
    // is not set for both GA & GTM.
    $assert_session->pageTextContains('Google Analytics is enabled. Please configure the API key.');
    $assert_session->pageTextContains('Google Tag Manager is enabled. Please configure the API key.');

    // Visit Google Analytics configuration page and set the API key.
    $page->clickLink('Google Analytics');
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldExists('Web Property ID');
    $page->fillField('Web Property ID', 'UA-334567-6789078908');
    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The configuration options have been saved.');

    // Go back to the tour.
    $this->drupalGet('/admin/tour');
    $assert_session->statusCodeEquals(200);

    // Assert that a link to the Google Analytics configuration page appears.
    $assert_session->linkExists('Google Analytics');

    // Assert that the status warning does NOT appear for GA.
    $assert_session->pageTextNotContains('Google Analytics is enabled. Please configure the API key here.');
    $assert_session->pageTextContains('Google Analytics is enabled and configured.');

    // Visit Google Tag Manager configuration page.
    $page->clickLink('Google Tag Manager');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('There are no containers yet.');

    // Add Google Tag Manager containers.
    $this->drupalGet('/admin/config/system/google-tag/add');
    $assert_session->statusCodeEquals(200);
    // Assert required field exists.
    $assert_session->fieldExists('Label');
    $assert_session->fieldExists('Container ID');
    $page->fillField('Label', 'Test container');
    $page->fillField('Machine-readable name', 'test_container');
    $page->fillField('Container ID', 'GTM-345678');

    $page->pressButton('Save');
    $assert_session->pageTextContains('Created 3 snippet files for Test container container based on configuration.');

    // Go back to the tour.
    $this->drupalGet('/admin/tour');
    $assert_session->statusCodeEquals(200);

    // Assert that a link to the Google Tag Manager configuration page appears.
    $assert_session->linkExists('Google Tag Manager');

    // Assert that the status warning does NOT appear.
    $assert_session->pageTextNotContains('Google Tag Manager is enabled. Please configure the API key here.');
    $assert_session->pageTextContains('Google Tag Manager is enabled and configured.');
  }

}
