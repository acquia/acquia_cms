<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Ensure that login works as expected.
 *
 * @group acquia_cms_common
 * @group acquia_cms
 * @group medium_risk
 */
class UserLoginFloodTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_common',
    'user',
  ];

  /**
   * Tests the per-user login flood control.
   */
  public function testUserLoginFloodControl(): void {
    $this->config('user.flood')
      ->set('ip_limit', 4000)
      ->set('user_limit', 5)
      ->save();

    $user = $this->drupalCreateUser([]);
    $incorrectUser = clone $user;
    $incorrectUser->passRaw .= 'incorrect';

    // Try 4 failed logins.
    for ($i = 0; $i < 4; $i++) {
      $this->assertFailedLogin($incorrectUser);
    }

    // Try login with a user.
    $this->drupalLogin($user);
    $this->drupalLogout();

    // Try 5 failed logins.
    for ($i = 0; $i < 5; $i++) {
      $this->assertFailedLogin($incorrectUser);
    }
    // Try login with actual user credentials.
    $this->assertFailedLogin($user, 'user');
  }

  /**
   * Make an unsuccessful login attempt.
   *
   * @param \Drupal\user\Entity\User $account
   *   A user object with name and passRaw attributes for the login attempt.
   * @param string $flood_trigger
   *   (optional) Whether or not to expect that the flood control mechanism.
   */
  public function assertFailedLogin(User $account, string $flood_trigger = NULL): void {
    $assert = $this->assertSession();
    $userLogin = [
      'name' => $account->getAccountName(),
      'pass' => $account->passRaw,
    ];
    $this->drupalGet('user/login');
    $this->submitForm($userLogin, 'Log in');
    if (isset($flood_trigger)) {
      $assert->statusCodeEquals(403);
      $assert->fieldNotExists('pass');
      $this->assertSession()->pageTextMatches("/There (has|have) been more than \w+ failed login attempt.* for this account. It is temporarily blocked. Try again later or request a new password./");
      $this->assertSession()->linkExists("request a new password");
    }
    else {
      $assert->statusCodeEquals(200);
      $assert->fieldValueEquals('pass', '');
      $assert->pageTextContains('Unrecognized username or password. Forgot your password?');
    }
  }

}
