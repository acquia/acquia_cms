<?php

namespace Drupal\acquia_cms_headless;

/**
 * Base class for acquia_cms_headless plugins.
 */
abstract class AcquiaCmsHeadlessPluginBase implements AcquiaCmsHeadlessInterface {

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
    return $this->pluginDefinition['weight'];
  }

}
