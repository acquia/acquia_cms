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
   * Tests the filter black_list_html_tags.
   */
  public function testFilterBlackListHtmlTags() {
    $assert_session = $this->assertSession();
    $account = $this->drupalCreateUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    // Visit the filter page.
    $this->drupalGet('/admin/config/content/formats/manage/filtered_html');
    $assert_session->statusCodeEquals(200);
    $filter_element = $assert_session->elementExists('css', '#edit-filters-black-list-html-tags-status');
    $this->assertFalse($filter_element->isChecked(), 'Expect uncheck, but found checked.');
  }

}
