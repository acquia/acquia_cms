<?php

namespace Drupal\Tests\acquia_cms_common\FunctionalJavascript;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Behat\Mink\Element\ElementInterface;
use Drupal\acquia_cms_common\Facade\PermissionFacade;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\MediaType;
use Drupal\Tests\acquia_cms_common\Traits\MediaTestTrait;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Base class for testing CKEditor5 embeds of a specific media type.
 */
abstract class MediaEmbedTestBase extends WebDriverTestBase {

  use CKEditor5TestTrait;
  use MediaTestTrait;
  use TestFileCreationTrait;

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
  protected function setUp(): void {
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

    // The content model roles (i.e. content_administrator, content_author
    // & content_editor) gets created only when any of the content model module
    // is enabled. @see acquia_cms_common_module_preinstall().
    // So, we need to create those roles on the fly and
    // test if it gets created successfully by our facade.
    $permissionFacadeObj = \Drupal::classResolver(PermissionFacade::class);
    $this->assertEquals($permissionFacadeObj->addRole('content_administrator'), SAVED_NEW);
    $this->assertEquals($permissionFacadeObj->addRole('content_author'), SAVED_NEW);
    $this->assertEquals($permissionFacadeObj->addRole('content_editor'), SAVED_NEW);
  }

  /**
   * Tests embedding media in CKEditor5.
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
  }

  /**
   * Returns the path of a test file to upload into the media library.
   *
   * @param string $type
   *   The file type. This can be any of the types accepted by
   *   \Drupal\Tests\TestFileCreationTrait::getTestFiles().
   *
   * @return string
   *   The absolute path to the test file.
   */
  protected function getTestFilePath($type) {
    $files = $this->getTestFiles($type);
    $this->assertNotEmpty($files);
    $uri = reset($files)->uri;
    $path = $this->container->get('file_system')->realpath($uri);
    $this->assertNotEmpty($path);
    $this->assertFileExists($path);
    return $path;
  }

  /**
   * Tests creating a new media item in the media library.
   */
  protected function doTestCreateMedia() {
    $this->openMediaLibrary();
    $this->addMedia();
    $added_media = $this->assertSession()->waitForElementVisible('css', '.js-media-library-add-form-added-media > li');
    $this->assertNotEmpty($added_media);
    $this->assertAddedMedia($added_media);
  }

  /**
   * Asserts required fields of a media item being created in the media library.
   *
   * @param \Behat\Mink\Element\ElementInterface $added_media
   *   The element containing the required fields of the media item being
   *   created.
   */
  protected function assertAddedMedia(ElementInterface $added_media) {
    // Nothing to do by default.
  }

  /**
   * Begins creating a media item in the media library.
   *
   * Normally this should enter the source field value for the new media item
   * (i.e., upload an image or file, enter a video URL, etc.)
   */
  protected function addMedia() {
    // Nothing to do by default.
  }

  /**
   * Asserts that an embedded media item is visible in CKEditor5.
   */
  protected function assertMediaIsEmbedded() {
    $result = $this->assertSession()->waitForElementVisible('css', '.ck-content .ck-widget.drupal-media .media');
    $this->assertNotEmpty($result);
  }

  /**
   * Inserts all selected media into CKEditor5 and closes the media library.
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
   * Opens the media library in CKEditor5.
   */
  protected function openMediaLibrary() {
    // Exit the CKEditor5 iFrame if we're in it.
    $this->getSession()->switchToIFrame(NULL);
    $this->waitForEditor();

    // The `Show more items` button is clicked, because when tests running
    // on mobile device makes ckeditor5 responsive and requires click to
    // additional button to navigate to other elements.
    $this->pressEditorButton('Show more items');
    $this->pressEditorButton('Insert Media');
    $result = $this->assertSession()->waitForText('Add or select media');
    $this->assertNotEmpty($result);
  }

}
