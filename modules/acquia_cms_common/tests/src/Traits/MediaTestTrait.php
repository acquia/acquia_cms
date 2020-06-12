<?php

namespace Drupal\Tests\acquia_cms_common\Traits;

use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;

/**
 * Provides helper methods for tests which work with media items.
 */
trait MediaTestTrait {

  /**
   * Creates a media entity.
   *
   * @param array $values
   *   (optional) Values to create the media entity with. If no value is
   *   provided for the media entity's source field, a randomly generated one
   *   will be used.
   *
   * @return \Drupal\media\MediaInterface
   *   The new, saved media item.
   */
  protected function createMedia(array $values = []) : MediaInterface {
    $media = Media::create($values);
    $media_type = $media->bundle->entity;

    $source_field = $media->getSource()
      ->getSourceFieldDefinition($media_type)
      ->getName();

    if ($media->get($source_field)->isEmpty()) {
      $media->set($source_field, $this->generateSourceFieldValue($media_type, FALSE));
    }
    $media->save();

    return $media;
  }

  /**
   * Generates a random source field value for a media type.
   *
   * @param \Drupal\media\MediaTypeInterface $media_type
   *   The media type.
   * @param bool $normalize
   *   (optional) Whether to return only the normalized value, i.e., the value
   *   of the main property. Defaults to TRUE.
   *
   * @return mixed
   *   The randomly generated field value. If $normalize is TRUE, only the
   *   "main" value will be returned; for example, the image file referenced by
   *   the source field. Otherwise, the entire value and all of its properties
   *   will be returned as an array.
   */
  protected function generateSourceFieldValue(MediaTypeInterface $media_type, bool $normalize = TRUE) {
    $field_definition = $media_type->getSource()
      ->getSourceFieldDefinition($media_type);

    $generator = [
      $field_definition->getItemDefinition()->getClass(),
      'generateSampleValue',
    ];
    $value = $generator($field_definition);
    $this->assertInternalType('array', $value);
    $this->assertNotEmpty($value);

    if ($normalize) {
      $storage_definition = $field_definition->getFieldStorageDefinition();

      $property = $storage_definition->getMainPropertyName();
      $this->assertNotEmpty($property);
      $this->assertArrayHasKey($property, $value);

      if ($property === 'target_id') {
        return $this->container->get('entity_type.manager')
          ->getStorage($storage_definition->getSetting('target_type'))
          ->load($value[$property]);
      }
      else {
        return $value[$property];
      }
    }
    else {
      return $value;
    }
  }

}
