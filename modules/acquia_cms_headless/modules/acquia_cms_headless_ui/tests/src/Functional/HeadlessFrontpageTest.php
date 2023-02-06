<?php

namespace Drupal\Tests\acquia_cms_headless_ui\Functional;

use Drupal\Tests\acquia_cms_headless\Functional\HeadlessBrowserTestBase;

/**
 * Tests for acquia_cms_headless frontpage.
 *
 * @group acquia_cms_headless
 * @group low_risk
 */
class HeadlessFrontpageTest extends HeadlessBrowserTestBase {

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
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', '.user-login-form');
  }

  /**
   * Assert that frontpage for logged-in user is admin/content page.
   */
  public function testFrontPageIsAdminContentPage(): void {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);
    $assert_session = $this->assertSession();
    $assert_session->addressEquals('/admin/content');
  }

}
