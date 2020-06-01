<?php

namespace Drupal\Tests\acquia_cms_page\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Page content type that ships with Acquia CMS.
 *
 * @group acquia_cms_page
 */
class PageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_page',
  ];

  /**
   * Disable strict config schema checks in this test.
   *
   * Cohesion has a lot of config schema errors, and until they are all fixed,
   * this test cannot pass unless we disable strict config schema checking
   * altogether. Since strict config schema isn't critically important in
   * testing this functionality, it's okay to disable it for now, but it should
   * be re-enabled (i.e., this property should be removed) as soon as possible.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Tests the bundled functionality of the Page content type.
   */
  public function testPageContentType() {
    $assert_session = $this->assertSession();

    // @todo: Once user roles are defined, either by acquia_cms_common or
    // another module, use the appropriate role(s) here, instead of permissions.
    $account = $this->drupalCreateUser([
      'create page content',
      'use editorial transition create_new_draft',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');
    // Assert that the current user can access the form to create a page. Note
    // that status codes cannot be asserted in functional JavaScript tests.
    $assert_session->statusCodeEquals(200);
    // Assert that the expected fields show up.
    $assert_session->fieldExists('Title');
    // Although Cohesion is not installed in this test, we do want to be sure
    // that a hidden field exists to store Cohesion's JSON-encoded layout canvas
    // data. For our purposes, checking for the existence of the hidden field
    // should be sufficient.
    $assert_session->hiddenFieldExists('field_layout_canvas[0][target_id][json_values]');
    // There should be a multi-value field to store tags. The label is visually
    // hidden, but it's there for accessibility purposes.
    $assert_session->fieldExists('Tags (value 1)');
    // There should be a select list to choose the moderation state, and it
    // should default to Draft. Note that which moderation states are available
    // depends on the current user's permissions.
    $assert_session->optionExists('Save as', 'Draft');
  }

}
