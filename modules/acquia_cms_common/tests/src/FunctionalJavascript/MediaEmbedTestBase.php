<?php

namespace Drupal\Tests\acquia_cms_common\FunctionalJavascript;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\MediaType;
use Drupal\Tests\acquia_cms_common\Traits\MediaLibraryCreationTrait;
use Drupal\Tests\acquia_cms_common\Traits\MediaTestTrait;
use Drupal\Tests\ckeditor\Traits\CKEditorTestTrait;

/**
 * Base class for testing CKEditor embeds of a specific media type.
 */
abstract class MediaEmbedTestBase extends WebDriverTestBase {

  use CKEditorTestTrait;
  use MediaTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  protected static $modules = [
    'media_library',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // @todo Remove this check when Acquia Cloud IDEs support running functional
    // JavaScript tests.
    if (AcquiaDrupalEnvironmentDetector::isAhIdeEnv()) {
      $this->markTestSkipped('This test cannot run in an Acquia Cloud IDE.');
    }

    // Ensure that the media type under test has been specified by a subclass.
    $this->assertNotEmpty($this->mediaType);

    // Do normal set-up and ensure that the media type actually exists.
    parent::setUp();
    $media_type = MediaType::load($this->mediaType);
    $this->assertInstanceOf(MediaType::class, $media_type);

    // Create a media item of the type under test.
    $this->createMedia([
      'bundle' => $media_type->id(),
    ]);
  }

  /**
   * Tests embedding media in CKEditor.
   */
  public function testEmbedMedia() {
    if (AcquiaDrupalEnvironmentDetector::isAhIdeEnv()) {
      $this->markTestSkipped('This cannot be run in a Cloud IDE right now');
    }
    $node_type = $this->drupalCreateContentType()->id();
    user_role_grant_permissions('content_author', [
      "create $node_type content",
    ]);

    $account = $this->drupalCreateUser();
    $account->addRole('content_author');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet("/node/add/$node_type");
    $this->openMediaLibrary();
    $this->selectMedia(0);
    $this->insertSelectedMedia();
    $this->assertMediaIsEmbedded();
    // Exit the CKEditor iFrame.
    $this->getSession()->switchToIFrame(NULL);

    // If this class wants to test creating media in the media library, do it.
    if (in_array(MediaLibraryCreationTrait::class, class_uses(static::class), TRUE)) {
      $this->openMediaLibrary();
      $this->addMedia();
      $added_media = $this->assertSession()->waitForElementVisible('css', '.js-media-library-add-form-added-media > li');
      $this->assertNotEmpty($added_media);
      $this->assertAddedMedia($added_media);
    }
  }

  /**
   * Asserts that an embedded media item is visible in CKEditor.
   */
  protected function assertMediaIsEmbedded() {
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');

    $result = $this->assertSession()->waitForElementVisible('css', 'drupal-media');
    $this->assertNotEmpty($result);
  }

  /**
   * Inserts all selected media into CKEditor and closes the media library.
   */
  protected function insertSelectedMedia() {
    $this->assertSession()
      ->elementExists('css', '.ui-dialog-buttonpane')
      ->pressButton('Insert selected');
  }

  /**
   * Selects a media item in the media library.
   *
   * @param int $position
   *   The zero-based index of the media item to select.
   */
  protected function selectMedia(int $position) {
    $checkbox = $this->assertSession()
      ->waitForElementVisible('named', ['field', "media_library_select_form[$position]"]);
    $this->assertNotEmpty($checkbox);
    $checkbox->check();
  }

  /**
   * Opens the media library in CKEditor.
   */
  protected function openMediaLibrary() {
    $this->waitForEditor();
    $this->pressEditorButton('drupalmedialibrary');

    $result = $this->assertSession()->waitForText('Add or select media');
    $this->assertNotEmpty($result);
  }

}
