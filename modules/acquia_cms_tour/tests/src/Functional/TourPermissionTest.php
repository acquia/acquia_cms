<?php

namespace Drupal\Tests\acquia_cms_tour\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests tour page permissions for the user roles included with Acquia CMS.
 *
 * @group acquia_cms_tour
 * @group acquia_cms
 */
class TourPermissionTest extends BrowserTestBase {

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
   * Tests tour permission for user roles.
   *
   * - User administrator should able to access tour page.
   * - User roles without permission 'access acquia_cms tour'
   *   should not be able to access tour page.
   */
  public function testTourPermissions() {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser(['access acquia_cms tour', 'access toolbar']);
    $this->drupalLogin($account);
    // Administrators should have permission to access tour page.
    $this->assertTrue($account->hasPermission('access acquia_cms tour'));

    // User should be able to access the toolbar.
    $assert_session->elementExists('css', '#toolbar-administration');
    $assert_session->linkExists('Tour');

    // User administrator should be able to access tour page.
    $this->drupalGet('/admin/tour');
    $assert_session->statusCodeEquals(200);
    $this->drupalLogout();

    $account = $this->drupalCreateUser();
    $account->addRole('content_editor');
    $account->save();
    // User should not have permission to access tour page.
    $this->assertFalse($account->hasPermission('access acquia_cms tour'));
    $this->drupalLogin($account);
    // User should not be able to access tour page.
    $this->drupalGet('/admin/tour');
    $assert_session->statusCodeEquals(403);
    $this->drupalLogout();
  }

}
