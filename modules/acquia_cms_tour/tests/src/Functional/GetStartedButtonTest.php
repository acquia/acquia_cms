<?php

namespace Drupal\Tests\acquia_cms_tour\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the dasboard redirection button access.
 *
 * @group acquia_cms
 * @group acquia_cms_tour
 */
class GetStartedButtonTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_tour',
  ];

  /**
   * Tests that specified roles have no access to the dashboard.
   *
   * @param string $role
   *   The ID of the user role to test with.
   *
   * @dataProvider providerNoButtonAccess
   */
  public function testNoButtonAccess(string $role) {
    $assert_session = $this->assertSession();

    $account = $this->createUser();
    $account->addRole($role);
    $account->save();
    $this->drupalLogin($account);

    // Visit the admin tour page.
    $this->drupalGet('/admin/tour');
    $assert_session->statusCodeEquals(200);

    $assert_session->elementNotExists('css', '.button-section');
  }

  /**
   * Tests that specified roles have access to the dashboard.
   *
   * @param string $role
   *   The ID of the user role to test with.
   *
   * @dataProvider providerButtonAccess
   */
  public function testButtonAccess(string $role) {
    $assert_session = $this->assertSession();

    $account = $this->createUser();
    $account->addRole($role);
    $account->save();
    $this->drupalLogin($account);

    // Visit the admin tour page.
    $this->drupalGet('/admin/tour');
    $assert_session->statusCodeEquals(200);

    $assert_session->elementExists('css', '.button-section');
  }

  /**
   * Data provider for ::testNoButtonAccess().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerNoButtonAccess() {
    return [
      ['content_author'],
      ['content_editor'],
    ];
  }

  /**
   * Data provider for ::testButtonAccess().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerButtonAccess() {
    return [
      ['administrator'],
      ['content_administrator'],
    ];
  }

}
