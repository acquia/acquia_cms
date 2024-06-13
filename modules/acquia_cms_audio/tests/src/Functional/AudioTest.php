<?php

namespace Drupal\Tests\acquia_cms_audio\Functional;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\acquia_cms_common\Functional\MediaTypeTestBase;

/**
 * Tests the Audio media type that ships with Acquia CMS.
 *
 * @group acquia_cms_audio
 * @group acquia_cms
 * @group medium_risk
 * @group push
 * @group pr
 */
class AudioTest extends MediaTypeTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['acquia_cms_audio'];


  /**
   * {@inheritdoc}
   */
  protected $mediaType = 'audio';

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
    $assert_session->fieldExists('Soundcloud audio URL');
    $assert_session->fieldExists('Categories');
    $assert_session->fieldExists('Tags');
    // The standard Categories and Tags fields should be present.
    $this->assertCategoriesAndTagsFieldsExist();

    // Assert that the fields are in the correct order.
    $this->assertFieldsOrder([
      'name',
      'field_media_soundcloud',
      'field_categories',
      'field_tags',
    ]);

    // Submit the form and ensure that we see the expected error message(s).
    $page->pressButton('Save');
    $assert_session->pageTextContains('Name field is required.');

    // Fill in the required fields and assert that things went as expected.
    $page->fillField('Name', 'Decoupled Drupal Podcast with Third & Grove and Acquia');
    $page->fillField('Soundcloud audio URL', 'https://soundcloud.com/ndigithq/is-drupal-an-established-cms');
    // For convenience, the parent class creates a few categories during set-up.
    // @see \Drupal\Tests\acquia_cms_common\Functional\ContentModelTestBase::setUp()
    $page->selectFieldOption('Categories', 'Music');
    $page->fillField('Tags', 'techno');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Audio Decoupled Drupal Podcast with Third & Grove and Acquia has been created.');

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

}
