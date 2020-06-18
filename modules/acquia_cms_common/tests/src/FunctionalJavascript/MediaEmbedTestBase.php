<?php

namespace Drupal\Tests\acquia_cms_common\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\MediaType;
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
