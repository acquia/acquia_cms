<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the filter filter_html.
 *
 * @group acquia_cms
 * @group acquia_cms_common
 */
class FilterFormatTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_common',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer filters']);
    $this->drupalLogin($account);
  }

  /**
   * Tests the filter filter_html.
   *
   * @param string $filter_format
   *   The filter format name.
   * @param bool $status
   *   Status of the field.
   *
   * @dataProvider providerFilterFormat
   */
  public function testFilterBlackListHtmlTags(string $filter_format, bool $status) {
    $assert_session = $this->assertSession();

    // Visit the filter page.
    $this->drupalGet('/admin/config/content/formats/manage/' . $filter_format);
    $assert_session->statusCodeEquals(200);
    $filter_element = $assert_session->elementExists('css', '#edit-filters-filter-html-status');
    $this->assertSame($status, $filter_element->isChecked(), 'Expect checked, but found uncheck.');

  }

  /**
   * Defines an array of modules & permissions to roles.
   */
  public static function providerFilterFormat(): array {
    return [
      [
        'full_html',
        TRUE,
      ],
      [
        'filtered_html',
        TRUE,
      ],
    ];
  }

}
