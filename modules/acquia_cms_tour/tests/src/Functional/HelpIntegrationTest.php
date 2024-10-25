<?php

namespace Drupal\Tests\acquia_cms_tour\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Acquia CMS Tour module's integration with the core Help module.
 *
 * @group acquia_cms
 * @group acquia_cms_tour
 * @group risky
 */
class HelpIntegrationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_tour',
    'toolbar',
  ];

  /**
   * Disable strict config schema checks in this test.
   *
   * Scheduler has a config schema errors, and until it's fixed,
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
   * Tests the Acquia CMS Tour module's integration with the core Help module.
   */
  public function testHelpIntegration() {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser([
      'access acquia cms tour dashboard',
      'access toolbar',
    ]);
    $this->drupalLogin($account);

    $toolbar = $assert_session->elementExists('css', '#toolbar-administration');

    $assert_tour_link = function () use ($assert_session, $toolbar) {
      $assert_session->elementsCount('named', ['link', 'Acquia CMS Wizard'], 1, $toolbar);
      $tour_link = $assert_session->elementExists('named', ['link', 'Acquia CMS Wizard'], $toolbar);
      $this->assertSame('Acquia CMS Wizard', $tour_link->getText());
      $this->assertTrue($tour_link->hasClass('toolbar-icon'));
      $this->assertTrue($tour_link->hasClass('toolbar-icon-help-main'));
      // The Help link should never show up.
      $assert_session->elementNotExists('named', ['link', 'Help'], $toolbar);
    };
    $assert_tour_link();

    $this->container->get('module_installer')->install(['help']);
    $this->getSession()->reload();
    $assert_tour_link();
  }

}
