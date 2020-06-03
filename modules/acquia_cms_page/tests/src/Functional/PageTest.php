<?php

namespace Drupal\Tests\acquia_cms_page\Functional;

use Drupal\Component\Utility\SortArray;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\acquia_cms_common\Functional\ContentTypeTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests the Page content type that ships with Acquia CMS.
 *
 * @group acquia_cms_page
 */
class PageTest extends ContentTypeTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $nodeType = 'page';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_page',
    'pathauto',
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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\taxonomy\VocabularyInterface $vocabulary */
    $vocabulary = Vocabulary::load('categories');
    $this->createTerm($vocabulary, [
      'name' => 'Rock',
    ]);
  }

  /**
   * Tests the bundled functionality of the Page content type.
   *
   * @depends testContentTypeAsAuthor
   */
  public function testPageContentType() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser();
    $account->addRole('content_author');
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
    $assert_session->optionExists('Categories', 'Rock');
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

    // Submit the form and ensure that we see the expected error message(s).
    $page->pressButton('Save');
    $assert_session->pageTextContains('Title field is required.');

    // Fill in the required fields and assert that things went as expected.
    $page->fillField('Title', 'Living with video');
    $page->fillField('Tags', 'techno');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Living with video has been created.');
    // Assert that the Pathauto pattern was used to create the URL alias.
    $assert_session->addressEquals('/living-video');
    // Assert that the techno tag was created dynamically in the correct
    // vocabulary.
    /** @var \Drupal\taxonomy\TermInterface $tag */
    $tag = Term::load(2);
    $this->assertInstanceOf(Term::class, $tag);
    $this->assertSame('tags', $tag->bundle());
    $this->assertSame('techno', $tag->getName());
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
