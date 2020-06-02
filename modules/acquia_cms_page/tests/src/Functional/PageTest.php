<?php

namespace Drupal\Tests\acquia_cms_page\Functional;

use Drupal\Component\Utility\SortArray;
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
   *
   * @var bool
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
    $assert_session->fieldExists('Search Description');
    // The search description should not have a summary.
    $assert_session->fieldNotExists('Summary');
    // There should be an auto-completing text field to store tags, and a select
    // list for choosing categories.
    $assert_session->elementAttributeExists('named', ['field', 'Tags'], 'data-autocomplete-path');
    $assert_session->selectExists('Categories');
    // There should be a field to add an image, and it should be using the
    // media library.
    $assert_session->elementExists('css', '#field_page_image-media-library-wrapper');
    // Although Cohesion is not installed in this test, we do want to be sure
    // that a hidden field exists to store Cohesion's JSON-encoded layout canvas
    // data. For our purposes, checking for the existence of the hidden field
    // should be sufficient.
    $assert_session->hiddenFieldExists('field_layout_canvas[0][target_id][json_values]');
    // There should be a select list to choose the moderation state, and it
    // should default to Draft. Note that which moderation states are available
    // depends on the current user's permissions.
    $assert_session->optionExists('Save as', 'Draft');
    // Assert that the fields are in the correct order.
    $this->assertFieldsOrder([
      'title',
      'body',
      'field_layout_canvas',
      'field_categories',
      'field_tags',
      'field_page_image',
      'moderation_state',
    ]);
  }

  /**
   * Asserts that the fields of the Page node form are in the correct order.
   *
   * @param string[] $expected_order
   *   The machine names of the fields we expect to be in the Page node type's
   *   form display, in the order we expect them to have.
   */
  private function assertFieldsOrder(array $expected_order) {
    $fields = $this->container->get('entity_display.repository')
      ->getFormDisplay('node', 'page')
      ->getComponents();

    uasort($fields, SortArray::class . '::sortByWeightElement');
    $fields = array_intersect(array_keys($fields), $expected_order);
    $this->assertSame($expected_order, array_values($fields));
  }

}
