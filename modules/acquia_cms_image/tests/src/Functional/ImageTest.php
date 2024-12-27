<?php

namespace Drupal\Tests\acquia_cms_image\Functional;

use Drupal\Component\Utility\SortArray;
use Drupal\Tests\BrowserTestBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Tests the Image media type that ships with Acquia CMS.
 *
 * @group acquia_cms_image
 * @group acquia_cms
 * @group medium_risk
 * @group push
 */
class ImageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'acquia_cms_image',
  ];

  /**
   * {@inheritdoc}
   */
  public function testImage() : void {
    $this->drupalLogin($this->rootUser);

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Add Categories vocabulary terms to the select list.
    $this->drupalGet("admin/structure/taxonomy/manage/categories/add");
    $page->fillField('Name', 'Music');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Created new term Music.');

    $this->drupalGet("/media/add/image");
    $assert_session->statusCodeEquals(200);

    // Assert that the expected fields show up.
    $assert_session->fieldExists('Name');
    $assert_session->fieldExists('Image');
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
    $group = $assert_session->elementExists('css', '#edit-image-wrapper');
    $assert_session->fieldExists('files[image_0]', $group);

    // Assert that the fields are in the correct order.
    $this->assertFieldsOrder([
      'name',
      'image',
      'field_categories',
      'field_tags',
    ]);

    // Submit the form and ensure that we see the expected error message(s).
    $page->pressButton('Save');
    $assert_session->pageTextContains('Name field is required.');
    $assert_session->pageTextContains('Image field is required.');

    // Fill in the required fields and assert that things went as expected.
    $page->fillField('Name', 'Living with Image');
    // For convenience, the parent class creates a few categories during set-up.
    // @see \Drupal\Tests\acquia_cms_common\Functional\ContentModelTestBase::setUp()
    $page->selectFieldOption('Categories', 'Music');
    $page->fillField('Tags', 'Techno');
    $page->attachFileToField('files[image_0]', $this->root . '/core/modules/media/tests/fixtures/example_1.jpeg');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Image Living with Image has been created.');

    // Assert that the techno tag was created dynamically in the correct
    // vocabulary.
    /** @var \Drupal\taxonomy\TermInterface $tag */
    $tag = Term::load(2);
    $this->assertInstanceOf(Term::class, $tag);
    $this->assertSame('tags', $tag->bundle());
    $this->assertSame('Techno', $tag->getName());

    // Media items are not normally exposed at standalone URLs, so assert that
    // the URL alias field does not show up.
    $assert_session->fieldNotExists('path[0][alias]');
    $this->testAuthorAccess();
    $this->testEditorAccess();
  }

  /**
   * Tests the media type as a content editor.
   *
   * Lets override parent's method so that we can change
   * media id here because we are adding one addition image
   * as default content for site logo, which breaks the
   * test if we use parents method.
   *
   * Asserts that content editor:
   * - Can edit others' media.
   * - Can delete others' media.
   */
  protected function testEditorAccess() {
    $this->drupalCreateRole([], 'content_editor');
    $account = $this->drupalCreateUser();
    $account->addRole('content_editor');
    $account->save();
    $this->drupalLogin($account);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Test that we can edit our own media.
    $this->drupalGet('/media/1/edit');
    $assert_session->statusCodeEquals(200);

    // Test that we can delete our own media.
    $this->drupalGet('/media/2/delete');
    $assert_session->statusCodeEquals(200);

  }

  /**
   * Tests the media type as a content author.
   *
   * Lets override parent's method so that we can change
   * media id here because we are adding one addition image
   * as default content for site logo, which breaks the
   * test if we use parents method.
   *
   * Asserts that content authors:
   * - Can create media of the type under test.
   * - Can edit their own media.
   * - Cannot edit others' media.
   * - Can delete their own media.
   * - Cannot delete others' media.
   */
  protected function testAuthorAccess() {
    $this->drupalCreateRole([], 'content_author');
    $account = $this->drupalCreateUser();
    $account->addRole('content_author');
    $account->save();
    $this->drupalLogin($account);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet("/media/add/image");
    $assert_session->statusCodeEquals(200);
    $page->fillField('Name', 'Pastafazoul!');
    $page->attachFileToField('files[image_0]', $this->root . '/core/modules/media/tests/fixtures/example_1.jpeg');
    $page->pressButton('Save');
    $assert_session->statusCodeEquals(200);

    // Test that we cannot edit others' media.
    $this->drupalGet('/media/1/edit');
    $assert_session->statusCodeEquals(403);

    // Test we can delete our own media.
    $this->drupalGet('/media/3/delete');
    $assert_session->statusCodeEquals(200);

    // Test that we cannot delete others' media.
    $this->drupalGet('/media/1/delete');
    $assert_session->statusCodeEquals(403);
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
      ->getFormDisplay('media', 'image')
      ->getComponents();

    $this->assertDisplayComponentsOrder($components, $expected_order, "The fields of the 'image' media type's edit form were not in the expected order.");
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
