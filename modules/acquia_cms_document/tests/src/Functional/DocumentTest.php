<?php

namespace Drupal\Tests\acquia_cms_document\Functional;

use Drupal\Component\Utility\SortArray;
use Drupal\Tests\BrowserTestBase;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Term;

/**
 * Tests the Document media type that ships with Acquia CMS.
 *
 * @group acquia_cms_document
 * @group acquia_cms
 * @group medium_risk
 * @group push
 * @group pr
 */
class DocumentTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'acquia_cms_document'
  ];

  /**
   * {@inheritdoc}
   */
  public function testDocument() : void {
    $this->drupalLogin($this->rootUser);

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Add Categories vocabulary terms to the select list.
    $this->drupalGet("admin/structure/taxonomy/manage/categories/add");
    $page->fillField('Name', 'Music');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Created new term Music.');

    $this->drupalGet("/media/add/document");
    $assert_session->statusCodeEquals(200);

    // Assert that the expected fields show up.
    $assert_session->fieldExists('Name');
    $assert_session->fieldExists('File');
    $assert_session->fieldExists('Categories');
    $assert_session->fieldExists('Tags');
    // The standard Categories and Tags fields should be present.
    $group = $assert_session->elementExists('css', '#edit-group-taxonomy');

    $tags = $assert_session->fieldExists('Tags', $group);
    $this->assertTrue($tags->hasAttribute('data-autocomplete-path'));

    $categories = $assert_session->selectExists('Categories', $group);
    // No item added to the select list.
    $this->assertTrue($categories->hasAttribute('multiple'));

    // Ensure that the select list has every term in the Categories vocabulary.
    $terms = $this->container->get('entity_type.manager')
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'vid' => 'categories',
      ]);

    /** @var \Drupal\taxonomy\TermInterface $term */
    foreach ($terms as $term) {
      $assert_session->optionExists('Categories', $term->label(), $group);
    }

    // Ensure Document field group is present and has document field.
    $group = $assert_session->elementExists('css', '#edit-field-media-file-wrapper');
    $assert_session->fieldExists('files[field_media_file_0]', $group);

    // Assert that the fields are in the correct order.
    $this->assertFieldsOrder([
      'name',
      'field_media_file',
      'field_categories',
      'field_tags',
    ]);

    // Submit the form and ensure that we see the expected error message(s).
    $page->pressButton('Save');
    $assert_session->pageTextContains('Name field is required.');
    $assert_session->pageTextContains('File field is required.');

    // Create a media asset.
    file_put_contents('public://file.txt', str_repeat('t', 10));
    $file = File::create([
      'uri' => 'public://file.txt',
      'filename' => 'file.txt',
    ]);
    $file->save();

    // Fill in the required fields and assert that things went as expected.
    $page->fillField('Name', 'A sample document');
    $page->selectFieldOption('Categories', 'Music');
    $page->fillField('Tags', 'Techno');
    $page->attachFileToField("files[field_media_file_0]", $this->container->get('file_system')->realpath('public://file.txt'));
    $page->pressButton('Save');
    $assert_session->pageTextContains('A sample document has been created.');

    // Assert that the techno tag was created dynamically in the correct
    // vocabulary.
    $this->drupalGet("/admin/structure/taxonomy/manage/tags/overview");
    /** @var \Drupal\taxonomy\TermInterface $tag */
    $tag = Term::load(2);
    $this->assertInstanceOf(Term::class, $tag);
    $this->assertSame('tags', $tag->bundle());
    $this->assertSame('Techno', $tag->getName());

    // See if the URL alias field is not shown.
    $assert_session->fieldNotExists('path[0][alias]');
  }

  /**
   * Asserts that the fields are in the correct order.
   *
   * @param string[] $expected_order
   *   The machine names of the fields we expect in media type's form display,
   *   in the order we expect them to have.
   */
  protected function assertFieldsOrder(array $expected_order) {
    $components = $this->container->get('entity_display.repository')
      ->getFormDisplay('media', 'document')
      ->getComponents();

    $this->assertDisplayComponentsOrder($components, $expected_order, "The fields of the 'document' media type's edit form were not in the expected order.");
  }

  /**
   * Asserts that the components of an entity display are in a specific order.
   *
   * @param array[] $components
   *   The components in the entity display.
   * @param string[] $expected_order
   *   The components' keys, in the expected order.
   * @param string $message
   *   (optional) A message if the assertion fails.
   */
  protected function assertDisplayComponentsOrder(array $components, array $expected_order, string $message = '') {
    uasort($components, SortArray::class . '::sortByWeightElement');
    $components = array_intersect(array_keys($components), $expected_order);
    $this->assertSame($expected_order, array_values($components), $message);
  }

}
