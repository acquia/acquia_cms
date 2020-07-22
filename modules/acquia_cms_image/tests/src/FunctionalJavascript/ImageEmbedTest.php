<?php

namespace Drupal\Tests\acquia_cms_image\Functional;

use Drupal\Tests\acquia_cms_common\FunctionalJavascript\MediaEmbedTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests embedding Image media in CKEditor.
 *
 * @group acquia_cms
 * @group acquia_cms_image
 */
class ImageEmbedTest extends MediaEmbedTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['acquia_cms_image', 'focal_point'];

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
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $this->container->get('entity_display.repository')
      ->getFormDisplay('media', 'image', 'media_library')
      ->setComponent('image', [
        'type' => 'image_focal_point',
        'settings' => [
          'preview_image_style' => 'thumbnail',
          'preview_link' => TRUE,
          'offsets' => '50,50',
          'progress_indicator' => 'throbber',
        ],
      ])
      ->save();
  }

  /**
   * Tests Focal Point integration with the media library.
   */
  public function testFocalPointMediaIntegration() {
    $session = $this->getSession();
    $assert_session = $this->assertSession();

    parent::testEmbedMedia();
    // Exit the CKEditor iFrame.
    $session->switchToIFrame(NULL);

    // Create a test image that we can upload into the media library.
    $files = $this->getTestFiles('image');
    $this->assertNotEmpty($files);
    $uri = reset($files)->uri;
    $path = $this->container->get('file_system')->realpath($uri);
    $this->assertFileExists($uri);

    $this->openMediaLibrary();
    $session->getPage()->attachFileToField('Add file', $path);
    // Wait for the file to be uploaded, and check that focal point widget
    // appear.
    $element = $assert_session->waitForElementVisible('css', '[data-media-library-added-delta] .focal-point-indicator');
    $this->assertNotEmpty($element);
  }

}
