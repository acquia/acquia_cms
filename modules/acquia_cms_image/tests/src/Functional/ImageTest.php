<?php

namespace Drupal\Tests\acquia_cms_image\Functional;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\acquia_cms_common\Functional\MediaTypeTestBase;

/**
 * Tests the Image media type that ships with Acquia CMS.
 *
 * @group acquia_cms_image
 * @group acquia_cms
 * @group medium_risk
 * @group push
 */
class ImageTest extends MediaTypeTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['acquia_cms_image'];

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
  protected $mediaType = 'image';

  /**
   * {@inheritdoc}
   */
  protected $fieldName = 'files[image_0]';

  /**
   * {@inheritdoc}
   */
  protected function doTestEditForm() : void {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser();
    $account->addRole('content_author');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('media/add/' . $this->mediaType);
    // Assert that the current user can access the form to create a image media.
    // Note that status codes cannot be asserted in functional JavaScript tests.
    $assert_session->statusCodeEquals(200);

    // Assert that the expected fields show up.
    $assert_session->fieldExists('Name');
    $assert_session->fieldExists('Add a new file');
    $assert_session->fieldExists('Categories');
    $assert_session->fieldExists('Tags');
    // The standard Categories and Tags fields should be present.
    $this->assertCategoriesAndTagsFieldsExist();

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
    $page->fillField('Tags', 'techno');
    $this->fillSourceField();
    $page->pressButton('Save');
    $assert_session->pageTextContains('Image Living with Image has been created.');

    // Assert that the techno tag was created dynamically in the correct
    // vocabulary.
    /** @var \Drupal\taxonomy\TermInterface $tag */
    $tag = Term::load(4);
    $this->assertInstanceOf(Term::class, $tag);
    $this->assertSame('tags', $tag->bundle());
    $this->assertSame('techno', $tag->getName());

    // Media items are not normally exposed at standalone URLs, so assert that
    // the URL alias field does not show up.
    $assert_session->fieldNotExists('path[0][alias]');
  }

  /**
   * Tests the media type as a content administrator.
   *
   * Lets override parent's method so that we can change
   * media id here because we are adding one addition image
   * as default content for site logo, which breaks the
   * test if we use parents method.
   *
   * Asserts that content administrators:
   * - Can create media of the type under test.
   * - Can edit their own media.
   * - Can edit others' media.
   * - Can delete their own media.
   * - Can delete others' media.
   */
  protected function doTestAdministratorAccess() {
    $account = $this->drupalCreateUser();
    $account->addRole('content_administrator');
    $account->save();
    $this->drupalLogin($account);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Test that we can create media.
    $this->drupalGet("/media/add/$this->mediaType");
    $assert_session->statusCodeEquals(200);
    // We should be able to select the language of the media item.
    $assert_session->selectExists('Language');
    $page->fillField('Name', 'Pastafazoul!');
    $this->fillSourceField();
    $page->pressButton('Save');
    $assert_session->statusCodeEquals(200);

    // Test that we can edit our own media.
    $this->drupalGet('/media/4/edit');
    $assert_session->statusCodeEquals(200);

    // Test that we can delete our own media.
    $this->drupalGet('/media/4/delete');
    $assert_session->statusCodeEquals(200);

    // Test that we can delete others' media.
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
  protected function doTestAuthorAccess() {
    $account = $this->drupalCreateUser();
    $account->addRole('content_author');
    $account->save();
    $this->drupalLogin($account);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet("/media/add/$this->mediaType");
    $assert_session->statusCodeEquals(200);
    // We should be able to select the language of the media item.
    $assert_session->selectExists('Language');
    $page->fillField('Name', 'Pastafazoul!');
    $this->fillSourceField();
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

}
