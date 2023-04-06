<?php

namespace Drupal\acquia_cms_tour;

/**
 * Base class for acquia_cms_tour plugins.
 */
abstract class AcquiaCmsStarterKitPluginBase implements AcquiaCmsStarterKitInterface {

  /**
   * The plugin definition.
   *
   * @var mixed
   */
  protected $pluginDefinition;

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
