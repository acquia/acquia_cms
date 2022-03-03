<?php

namespace Drupal\acquia_cms_tour;

use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface for acquia_cms_tour plugins.
 */
interface AcquiaCmsTourInterface extends PluginFormInterface {

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
