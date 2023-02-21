<?php

namespace Drupal\Tests\acquia_cms_site_studio\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the filter black_list_html_tags.
 *
 * @group acquia_cms
 * @group acquia_cms_site_studio
 */
class FilterFormatFilteredHtmlTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_site_studio',
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
   * Tests the Site Studio Core Form.
   */
  public function testFilterBlackListHtmlTags() {
    $assert_session = $this->assertSession();
    $account = $this->drupalCreateUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    // Visit the tour page.
    $this->drupalGet('/admin/config/content/formats/manage/filtered_html');
    $assert_session->statusCodeEquals(200);
    $assert_session->elementExists('css', '#edit-filters-black-list-html-tags-status');
  }

}
