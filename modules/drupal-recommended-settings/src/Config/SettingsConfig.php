<?php

namespace Acquia\Drupal\RecommendedSettings\Config;

use Consolidation\Config\Config;
use Grasmash\YamlExpander\YamlExpander;
use Psr\Log\NullLogger;

/**
 * The configuration for settings.
 */
class SettingsConfig extends Config {

  /**
   * Config Constructor.
   *
   * @param array $data
   *   Data array, if available.
   */
  public function __construct(array $data = []) {
    parent::__construct($data);
    $logger = new NullLogger();
    $this->expander = new YamlExpander($logger);
  }

  /**
   * Expands YAML placeholders in a given file, using config object.
   *
   * @param string $filename
   *   The file in which placeholders should be expanded.
   */
  public function expandFileProperties($filename) {
    $expanded_contents = $this->expander->expandArrayProperties(file($filename), $this->export());
    file_put_contents($filename, implode("", $expanded_contents));
  }

  /**
   * Set a config value.
   *
   * @param string $key
   *   The config key.
   * @param mixed $value
   *   The config value.
   *
   * @return $this
   */
  public function set($key, $value) {
    if ($value === 'false') {
      $value = FALSE;
    }
    elseif ($value === 'true') {
      $value = TRUE;
    }

    // Expand properties in string. We do this here so that one can pass
    // -D drush.alias=${drush.ci.aliases} at runtime and still expand
    // properties.
    if (is_string($value) && strstr($value, '$')) {
      $expanded = $this->expander->expandArrayProperties([$value], $this->export());
      $value = $expanded[0];
    }

    return parent::set($key, $value);

  }

  /**
   * Fetch a configuration value.
   *
   * @param string $key
   *   Which config item to look up.
   * @param string|null $defaultOverride
   *   Override usual default value with a different default. Deprecated;
   *   provide defaults to the config processor instead.
   *
   * @return mixed
   *   Mixed.
   */
  public function get($key, $defaultOverride = NULL) {
    $value = parent::get($key, $defaultOverride);

    // Last ditch effort to expand properties that may not have been processed.
    if (is_string($value) && strstr($value, '$')) {
      $expanded = $this->expander->expandArrayProperties([$value], $this->export());
      $value = $expanded[0];
    }

    return $value;
  }

}
