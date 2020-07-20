<?php

namespace Drupal\Tests\acquia_cms_document\Functional;

use Drupal\Tests\acquia_cms_common\FunctionalJavascript\MediaEmbedTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests embedding Document media in CKEditor.
 *
 * @group acquia_cms
 * @group acquia_cms_document
 */
class DocumentEmbedTest extends MediaEmbedTestBase {

  use TestFileCreationTrait;

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
   * {@inheritdoc}
   */
  public function testEmbedMedia() {
    $session = $this->getSession();
    $assert_session = $this->assertSession();

    parent::testEmbedMedia();
    // Exit the CKEditor iFrame.
    $session->switchToIFrame(NULL);

    // Create a test file that we can upload into the media library.
    $files = $this->getTestFiles('text');
    $this->assertNotEmpty($files);
    $uri = reset($files)->uri;
    $path = $this->container->get('file_system')->realpath($uri);
    $this->assertFileExists($uri);

    $this->openMediaLibrary();
    $session->getPage()->attachFileToField('Add file', $path);
    // Wait for the file to be uploaded, and the required fields (if any) to
    // appear.
    $element = $assert_session->waitForElementVisible('css', '.js-media-library-add-form-added-media > li');
    $this->assertNotEmpty($element);
    // Ensure that the "File" field is not present in the required fields.
    $assert_session->hiddenFieldNotExists('media[0][fields][field_media_file][0][fids]', $element);
  }

}
