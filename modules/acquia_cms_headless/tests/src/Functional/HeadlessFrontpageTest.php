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
    'views',
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
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    // Don't use one-time login links instead submit the login form.
    // @see https://www.drupal.org/project/drupal/issues/3469309
    if (isset($this->useOneTimeLoginLinks)) {
      $this->useOneTimeLoginLinks = FALSE;
    }
    $this->drupalLogin($account);
    $this->assertSession()->addressEquals('/admin/content');
    $this->assertSession()->statusCodeEquals(200);
  }

}
