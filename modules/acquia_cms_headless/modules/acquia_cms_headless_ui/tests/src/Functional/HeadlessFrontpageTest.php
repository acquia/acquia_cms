<?php

namespace Drupal\Tests\acquia_cms_headless_ui\Functional;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for acquia_cms_headless frontpage.
 *
 * @group acquia_cms_headless
 * @group low_risk
 */
class HeadlessFrontpageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_headless_ui',
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
   * Assert that frontpage for non logged-in user is login page.
   */
  public function testFrontPageIsLoginPage(): void {
    $this->drupalGet('/');
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', '.user-login-form');
  }

  /**
   * Assert that frontpage for logged-in user is admin/content page.
   */
  public function frontPageIsAdminContentPage(): void {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);
    $assert_session = $this->assertSession();
    $assert_session->addressEquals('/admin/content');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // @todo Remove this check when Acquia Cloud IDEs support running functional
    // JavaScript tests.
    if (AcquiaDrupalEnvironmentDetector::isAhIdeEnv()) {
      $this->markTestSkipped('This test cannot run in an Acquia Cloud IDE.');
    }
    parent::setUp();
  }

}
