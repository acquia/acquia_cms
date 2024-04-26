<?php

namespace Drupal\Tests\acquia_cms_document\FunctionalJavascript;

use Behat\Mink\Element\ElementInterface;
use Drupal\Tests\acquia_cms_common\FunctionalJavascript\MediaEmbedTestBase;

/**
 * Tests embedding Document media in CKEditor.
 *
 * @group acquia_cms
 * @group acquia_cms_document
 * @group medium_risk
 * @group push
 */
class DocumentEmbedTest extends MediaEmbedTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['acquia_cms_document'];

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
