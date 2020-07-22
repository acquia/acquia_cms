<?php

namespace Drupal\Tests\acquia_cms_common\Traits;

use Behat\Mink\Element\ElementInterface;
use Drupal\Tests\TestFileCreationTrait;

trait MediaLibraryCreationTrait {

  use TestFileCreationTrait;

  /**
   * Asserts required fields of a media item being created in the media library.
   *
   * @param \Behat\Mink\Element\ElementInterface $added_media
   *   The element containing the required fields of the media item being
   *   created.
   */
  abstract protected function assertAddedMedia(ElementInterface $added_media);

  /**
   * Begins creating a media item in the media library.
   *
   * Normally this should enter the source field value for the new media item
   * (i.e., upload an image or file, enter a video URL, etc.)
   */
  abstract protected function addMedia();

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

}
