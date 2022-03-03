<?php

namespace Drupal\acquia_cms_tour;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for acquia_cms_tour plugins.
 */
abstract class AcquiaCmsTourPluginBase extends PluginBase implements AcquiaCmsTourInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function weight() {
    return  $this->pluginDefinition['weight'];
  }

}
