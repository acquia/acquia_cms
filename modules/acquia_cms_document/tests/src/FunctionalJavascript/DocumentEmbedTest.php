<?php

namespace Drupal\Tests\acquia_cms_document\Functional;

use Behat\Mink\Element\ElementInterface;
use Drupal\Tests\acquia_cms_common\FunctionalJavascript\MediaEmbedTestBase;

/**
 * Tests embedding Document media in CKEditor.
 *
 * @group acquia_cms
 * @group acquia_cms_document
 */
class DocumentEmbedTest extends MediaEmbedTestBase {

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
    parent::testEmbedMedia();
    $this->doTestCreateMedia();
  }

  /**
   * {@inheritdoc}
   */
  protected function addMedia() {
    $this->getSession()
      ->getPage()
      ->attachFileToField('Add file', $this->getTestFilePath('text'));
  }

  /**
   * {@inheritdoc}
   */
  protected function assertAddedMedia(ElementInterface $added_media) {
    // Ensure that the "File" field is not present in the required fields.
    $this->assertSession()
      ->hiddenFieldNotExists('media[0][fields][field_media_file][0][fids]', $added_media);
  }

}
