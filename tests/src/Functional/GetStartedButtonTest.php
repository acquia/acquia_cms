<?php

namespace Drupal\Tests\acquia_cms\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the dashboard redirection button access.
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
    'acquia_cms_article',
    'acquia_cms_event',
    'acquia_cms_page',
  ];

  /**
   * Disable strict config schema checks in this test.
   *
   * There's some search_index missing schema error which we can skip for now.
   * Since strict config schema isn't critically important in
   * testing this functionality, it's okay to disable it for now, but it should
   * be re-enabled (i.e., this property should be removed) as soon as possible.
   *
   * @var bool
   */
  // @codingStandardsIgnoreStart
  protected $strictConfigSchema = FALSE;
  // @codingStandardsIgnoreEnd

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
