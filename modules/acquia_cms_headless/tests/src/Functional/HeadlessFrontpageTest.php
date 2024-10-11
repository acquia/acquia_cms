<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

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
   * Assert that frontpage for non logged-in user is login page.
   */
  public function testFrontPageIsLoginPage(): void {
    $this->drupalGet('/frontpage');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('css', '#user-login-form');
  }

  /**
   * Assert that frontpage for logged-in user is admin/content page.
   */
  public function testFrontPageIsAdminContentPage(): void {
    $account = $this->createUser(['access content overview'], '', TRUE);
    $page = $this->getSession()->getPage();

    // Login as admin user.
    $this->drupalGet('/user/login');
    $page->fillField('name', $account->getAccountName());
    $page->fillField('pass', $account->passRaw);
    $page->pressButton('Log in');

    // Assert that after login, user is redirected to admin/content page.
    $this->assertSession()->addressEquals('/admin/content');
    $this->assertSession()->statusCodeEquals(200);
  }

}
