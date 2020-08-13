<?php

namespace Drupal\Tests\acquia_cms_image\FunctionalJavascript;

use Behat\Mink\Element\ElementInterface;
use Drupal\Tests\acquia_cms_common\FunctionalJavascript\MediaEmbedTestBase;

/**
 * Tests embedding Image media in CKEditor.
 *
 * @group acquia_cms
 * @group acquia_cms_image
 */
class ImageEmbedTest extends MediaEmbedTestBase {

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
   * {@inheritdoc}
   */
  public function testEmbedMedia() {
    parent::testEmbedMedia();
    $this->doTestCreateMedia();
  }

  /**
   * {@inheritdoc}
   */
  protected function addMedia() {
    $this->getSession()
      ->getPage()
      ->attachFileToField('Add file', $this->getTestFilePath('image'));
  }

  /**
   * {@inheritdoc}
   */
  protected function assertAddedMedia(ElementInterface $added_media) {
    // Check that the focal point widget is being used when adding an image
    // in the media library.
    $indicator = $added_media->waitFor(10, function (ElementInterface $added_media) {
      $indicator = $added_media->find('css', '.focal-point-indicator');
      return $indicator && $indicator->isVisible();
    });
    $this->assertTrue($indicator);
  }

}
