<?php

namespace Drupal\acquia_cms_headless\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines acquia_cms_headless annotation object.
 *
 * @Annotation
 */
class AcquiaCmsHeadless extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * An integer to determine the weight of this plugin.
   *
   * @var int
   */
  public $weight = NULL;

}
