<?php

namespace Drupal\acquia_cms_tour;

/**
 * Interface for acquia_cms_tour plugins.
 */
interface AcquiaCmsTourInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

  /**
   * Returns the plugin weight.
   *
   * @return int
   *   The plugin weight.
   */
  public function weight();

}
