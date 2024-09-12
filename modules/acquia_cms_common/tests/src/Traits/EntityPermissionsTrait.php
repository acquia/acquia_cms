<?php

namespace Drupal\Tests\acquia_cms_common\Traits;

/**
 * Trait includes permissions matrix for Site Studio.
 */
trait EntityPermissionsTrait {

  /**
   * Function to get entity type.
   *
   * @return string
   *   Returns entity_type. Ex: content, media etc.
   */
  abstract public function getEntityType(): string;

  /**
   * Function to get entity bundle.
   *
   * @return string
   *   Returns bundle of entity. Ex: article, image etc.
   */
  abstract public function getBundle(): string;

}
