<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

use Drupal\file\FileInterface;
use Drupal\media\Entity\MediaType;
use Drupal\Tests\acquia_cms_common\Traits\MediaTestTrait;

/**
 * Base class for testing generic functionality of a specific media type.
 */
abstract class MediaTypeTestBase extends ContentModelTestBase {

  use MediaTestTrait {
    MediaTestTrait::createMedia as traitCreateMedia;
  }

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'views',
  ];

  /**
   * The machine name of the media type under test.
   *
   * This should be overridden by subclasses.
   *
   * @var string
   */
  protected $mediaType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Ensure that the media type under test has been specified by a subclass.
    $this->assertNotEmpty($this->mediaType);

    // Do normal set-up and ensure that the media type actually exists.
    parent::setUp();
    $media_type = MediaType::load($this->mediaType);
    $this->assertInstanceOf(MediaType::class, $media_type);

    // Create a media item of the type under test, belonging to user 1. This is
    // to test the capabilities of content editors and content administrators.
    $this->createMedia([
      'uid' => $this->rootUser->id(),
    ]);

    // Ensure that all fields in this media type are translatable.
    $this->assertConfigurableFieldsAreTranslatable('media', $this->mediaType);
  }

  /**
   * Tests the add/edit form of the media type under test.
   */
  abstract protected function doTestEditForm() : void;

  /**
   * Tests the access restrictions and add/edit form of the media type.
   */
  public function testMediaType() {
    $this->doTestAuthorAccess();
    $this->doTestEditorAccess();
    $this->doTestAdministratorAccess();
    $this->doTestEditForm();
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
  protected function doTestEditorAccess() {
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
    $this->drupalGet('/media/2/delete');
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
   * {@inheritdoc}
   */
  protected function createMedia(array $values = []) {
    $values += [
      'bundle' => $this->mediaType,
    ];
    return $this->traitCreateMedia($values);
  }

  /**
   * Fills in the source field on a media item's edit form.
   *
   * @param mixed $value
   *   The value to fill in the source field. If the media type under test is
   *   file-based, this should be an instance of \Drupal\file\FileInterface.
   */
  protected function fillSourceField($value = NULL) {
    $media_type = MediaType::load($this->mediaType);

    if ($value === NULL) {
      $value = $this->generateSourceFieldValue($media_type);
    }

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

  /**
   * Asserts that the fields are in the correct order.
   *
   * @param string[] $expected_order
   *   The machine names of the fields we expect in media type's form display,
   *   in the order we expect them to have.
   */
  protected function assertFieldsOrder(array $expected_order) {
    $components = $this->container->get('entity_display.repository')
      ->getFormDisplay('media', $this->mediaType)
      ->getComponents();

    $this->assertDisplayComponentsOrder($components, $expected_order, "The fields of the '$this->mediaType' media type's edit form were not in the expected order.");
  }

}
