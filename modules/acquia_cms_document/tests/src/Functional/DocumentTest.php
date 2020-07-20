<?php

namespace Drupal\Tests\acquia_cms_document\Functional;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\acquia_cms_common\Functional\MediaTypeTestBase;

/**
 * Tests the Document media type that ships with Acquia CMS.
 *
 * @group acquia_cms_document
 * @group acquia_cms
 */
class DocumentTest extends MediaTypeTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['acquia_cms_document'];

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
   * {@inheritdoc}
   */
  protected $mediaType = 'document';

  /**
   * Tests the functionality of the document media type.
   */
  public function testDocumentMediaType() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser();
    $account->addRole('content_author');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('media/add/' . $this->mediaType);
    // Assert that the current user can create a document media.
    $assert_session->statusCodeEquals(200);

    // Assert that the expected fields show up.
    $assert_session->fieldExists('Name');
    $assert_session->fieldExists('File');
    $assert_session->fieldExists('Categories');
    $assert_session->fieldExists('Tags');
    // The standard Categories and Tags fields should be present.
    $this->assertCategoriesAndTagsFieldsExist();
    // Ensure File field group is present and has file field.
    $group = $assert_session->elementExists('css', '#edit-field-media-file-wrapper');
    $assert_session->fieldExists('files[field_media_file_0]', $group);
    // We should be able to select the language of the media item.
    $assert_session->selectExists('Language');

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

    // Fill in the required fields and assert that things went as expected.
    $page->fillField('Name', 'A sample document');
    // For convenience, the parent class creates a few categories during set-up.
    // @see \Drupal\Tests\acquia_cms_common\Functional\ContentModelTestBase::setUp()
    $page->selectFieldOption('Categories', 'Music');
    $page->fillField('Tags', 'techno');
    $this->fillSourceField();
    $page->pressButton('Save');
    $assert_session->pageTextContains('A sample document has been created.');

    // Assert that the techno tag was created dynamically in the correct
    // vocabulary.
    /** @var \Drupal\taxonomy\TermInterface $tag */
    $tag = Term::load(4);
    $this->assertInstanceOf(Term::class, $tag);
    $this->assertSame('tags', $tag->bundle());
    $this->assertSame('techno', $tag->getName());

    // See if the URL alias field is not shown.
    $assert_session->fieldNotExists('path[0][alias]');
  }

}
