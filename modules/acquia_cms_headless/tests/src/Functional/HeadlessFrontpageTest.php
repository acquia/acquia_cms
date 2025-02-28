<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

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
    'node',
    'views',
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
    // Create an administrator role with is_admin set to true.
    Role::create([
      'id' => 'administrator',
      'label' => 'Administrator',
      'is_admin' => TRUE,
    ])->save();
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
