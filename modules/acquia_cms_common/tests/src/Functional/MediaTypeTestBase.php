<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Base class for testing generic functionality of a specific media type.
 */
abstract class MediaTypeTestBase extends ContentModelTestBase {

  use TestFileCreationTrait;

  /**
   * The machine name of the media type under test.
   *
   * This should be overridden by subclasses.
   *
   * @var string
   */
  protected $mediaType;

  /**
   * The source field value to use when creating a test media item.
   *
   * This should be overridden by subclasses if the media type under test is not
   * file-based. For example, if testing a media type that handles YouTube
   * videos, this should be the URL of a video to test with.
   *
   * @var mixed
   */
  protected $sourceValue;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Ensure that the media type under test has been specified by a subclass.
    $this->assertNotEmpty($this->mediaType);

    // Do normal set-up and ensure that the media type actually exists.
    parent::setUp();
    $media_type = MediaType::load($this->mediaType);
    $this->assertInstanceOf(MediaType::class, $media_type);

    // If the media type is file-based, prepare a few files to test with. The
    // source field item type determines what type of test files to prepare.
    $source_field_type = $media_type->getSource()
      ->getSourceFieldDefinition($media_type)
      ->getItemDefinition()
      ->getClass();

    if (is_a($source_field_type, FileItem::class, TRUE)) {
      $files = is_a($source_field_type, ImageItem::class, TRUE)
        ? $this->getTestFiles('image')
        : $this->getTestFiles('text');
      $this->assertNotEmpty($files, 'Test files were not generated.');

      $file = File::create([
        'uri' => reset($files)->uri,
      ]);
      $file->save();
      $this->sourceValue = $file;
    }
    // Tests for media types which are NOT file-based are expected to provide
    // a source value.
    $this->assertNotEmpty($this->sourceValue, 'A source value is needed in order to create a test media item.');

    // Create a media item of the type under test, belonging to user 1. This is
    // to test the capabilities of content editors and content administrators.
    $this->createMedia([
      'uid' => $this->rootUser->id(),
    ]);
  }

  /**
   * Tests that all configurable fields for the media type are translatable.
   */
  public function testAllFieldsAreTranslatable() {
    $this->assertConfigurableFieldsAreTranslatable('media', $this->mediaType);
  }

  /**
   * Tests the media type as a content author.
   *
   * Asserts that content authors:
   * - Can create media of the type under test.
   * - Can edit their own media.
   * - Cannot edit others' media.
   * - Can delete their own media.
   * - Cannot delete others' media.
   */
  public function testMediaTypeAsAuthor() {
    $account = $this->drupalCreateUser();
    $account->addRole('content_author');
    $account->save();
    $this->drupalLogin($account);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet("/media/add/$this->mediaType");
    $assert_session->statusCodeEquals(200);
    $page->fillField('Name', 'Pastafazoul!');
    // We have already ensured that $this->sourceValue is not empty.
    // @see ::setUp()
    $this->fillSourceField($this->sourceValue);
    $page->pressButton('Save');
    $assert_session->statusCodeEquals(200);

    // Test that we cannot edit others' media.
    $this->drupalGet('/media/1/edit');
    $assert_session->statusCodeEquals(403);

    // Test we can delete our own media.
    $this->drupalGet('/media/2/delete');
    $assert_session->statusCodeEquals(200);

    // Test that we cannot delete others' media.
    $this->drupalGet('/media/1/delete');
    $assert_session->statusCodeEquals(403);
  }

  /**
   * Tests the media type as a content editor.
   *
   * Asserts that content editors:
   * - Cannot create media of the type under test.
   * - Can edit their own media.
   * - Can edit others' media.
   * - Can delete their own media.
   * - Can delete others' media.
   */
  public function testMediaTypeAsEditor() {
    $account = $this->drupalCreateUser();
    $account->addRole('content_editor');
    $account->save();
    $this->drupalLogin($account);

    $media = $this->createMedia([
      'uid' => $account->id(),
    ]);

    $assert_session = $this->assertSession();

    // Test that we cannot create new media.
    $this->drupalGet("/media/add/$this->mediaType");
    $assert_session->statusCodeEquals(403);

    // Test that we can edit our own media.
    $this->drupalGet($media->toUrl('edit-form'));
    $assert_session->statusCodeEquals(200);

    // Test that we can delete our own media.
    $this->drupalGet($media->toUrl('delete-form'));
    $assert_session->statusCodeEquals(200);

    // Test that we can delete others' media.
    $this->drupalGet('/media/1/delete');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Tests the media type as a content administrator.
   *
   * Asserts that content administrators:
   * - Can create media of the type under test.
   * - Can edit their own media.
   * - Can edit others' media.
   * - Can delete their own media.
   * - Can delete others' media.
   */
  public function testMediaTypeAsAdministrator() {
    $account = $this->drupalCreateUser();
    $account->addRole('content_administrator');
    $account->save();
    $this->drupalLogin($account);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Test that we can create media.
    $this->drupalGet("/media/add/$this->mediaType");
    $assert_session->statusCodeEquals(200);
    $page->fillField('Name', 'Pastafazoul!');
    // We have already ensured that $this->sourceValue is not empty.
    // @see ::setUp()
    $this->fillSourceField($this->sourceValue);
    $page->pressButton('Save');
    $assert_session->statusCodeEquals(200);

    // Test that we can edit our own content.
    $this->drupalGet('/media/2/edit');
    $assert_session->statusCodeEquals(200);

    // Test that we can delete our own content.
    $this->drupalGet('/media/2/delete');
    $assert_session->statusCodeEquals(200);

    // Test that we can delete others' content.
    $this->drupalGet('/media/1/delete');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Creates a media item.
   *
   * @param array $values
   *   (optional) Field values with which to create the media item. By default,
   *   the media will be of the type under test, and $this->sourceValue will be
   *   the value of the source field.
   *
   * @return \Drupal\media\MediaInterface
   *   The new, saved media item.
   */
  protected function createMedia(array $values = []) {
    $this->assertNotEmpty($this->sourceValue, 'Cannot create a media item without a source field value.');

    $values += [
      'bundle' => $this->mediaType,
    ];
    /** @var \Drupal\media\MediaInterface $media */
    $media = Media::create($values);

    $source_field = $media->getSource()
      ->getSourceFieldDefinition($media->bundle->entity)
      ->getName();
    $media->set($source_field, $this->sourceValue)->save();
    return $media;
  }

  /**
   * Fills in the source field on a media item's edit form.
   *
   * @param mixed $value
   *   The value to fill in the source field. If the media type under test is
   *   file-based, this should be an instance of \Drupal\file\FileInterface.
   */
  protected function fillSourceField($value) {
    $media_type = MediaType::load($this->mediaType);

    $field = $media_type->getSource()
      ->getSourceFieldDefinition($media_type)
      ->getLabel();

    $page = $this->getSession()->getPage();
    if ($value instanceof FileInterface) {
      // Ensure that the file is accessible at a physical path.
      $uri = $value->getFileUri();
      $path = $this->container->get('file_system')->realpath($uri);
      $this->assertNotEmpty($path, 'The test file has no physical path.');
      $this->assertFileExists($path);
      $page->attachFileToField($field, $path);
    }
    else {
      $page->fillField($field, $value);
    }
  }

}
