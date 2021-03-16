<?php

namespace Drupal\Tests\acquia_cms_video\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\acquia_cms_common\Functional\MediaTypeTestBase;

/**
 * Tests the Video media type that ships with Acquia CMS.
 *
 * @group acquia_cms_video
 * @group acquia_cms
 * @group medium_risk
 * @group push
 * @group disabled
 */
class VideoTest extends MediaTypeTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['acquia_cms_video'];

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
  protected $mediaType = 'video';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Set the a default value for field_media_oembed_video so that we can
    // bypass the oEmbed system's URL validation. (It's not necessary for this
    // test anyway).
    FieldConfig::loadByName('media', $this->mediaType, 'field_media_oembed_video')
      ->setDefaultValue('https://www.youtube.com/watch?v=6e8QyfvQMmU&list=PLpVC00PAQQxHzlDeQvCNDKkyKRV1G3_vT')
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestEditForm() : void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser();
    $account->addRole('content_author');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('media/add/' . $this->mediaType);
    // Assert that the current user can access the form to create a video media.
    // Note that status codes cannot be asserted in functional JavaScript tests.
    $assert_session->statusCodeEquals(200);

    // Assert that the expected fields show up.
    $assert_session->fieldExists('Name');
    $assert_session->fieldExists('Remote video URL');
    $assert_session->fieldExists('Categories');
    $assert_session->fieldExists('Tags');
    // The standard Categories and Tags fields should be present.
    $this->assertCategoriesAndTagsFieldsExist();

    // Assert that the fields are in the correct order.
    $this->assertFieldsOrder([
      'name',
      'field_media_oembed_video',
      'field_categories',
      'field_tags',
    ]);

    // Submit the form and ensure that we see the expected error message(s).
    $page->pressButton('Save');
    $assert_session->pageTextContains('Name field is required.');

    // Fill in the required fields and assert that things went as expected.
    $page->fillField('Name', 'Drupal 8 Beginners, Lesson 01: Intro to the Course');
    $page->fillField('Remote video URL', 'https://www.youtube.com/watch?v=6e8QyfvQMmU&list=PLpVC00PAQQxHzlDeQvCNDKkyKRV1G3_vT');
    // For convenience, the parent class creates a few categories during set-up.
    // @see \Drupal\Tests\acquia_cms_common\Functional\ContentModelTestBase::setUp()
    $page->selectFieldOption('Categories', 'Music');
    $page->fillField('Tags', 'techno');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Video Drupal 8 Beginners, Lesson 01: Intro to the Course has been created.');

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
   * {@inheritdoc}
   */
  protected function fillSourceField($value = NULL) {
    // Override this method so that it does not do anything with
    // field_media_oembed_video. We are setting default value already in setUp()
    // so that we can bypass the oEmbed system's URL validation, since it is not
    // needed in this test and actively gets in the way.
  }

}
