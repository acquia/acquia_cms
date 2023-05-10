<?php

namespace Acquia\Drupal\RecommendedSettings\Config;

use Acquia\Drupal\RecommendedSettings\Common\ArrayManipulator;
use Consolidation\Config\Loader\ConfigProcessor;

/**
 * Custom processor for YAML based configuration.
 */
class YamlConfigProcessor extends ConfigProcessor {

  /**
   * Expand dot notated keys.
   *
   * @param array $config
   *   The configuration to be processed.
   *
   * @return array
   *   The processed configuration
   */
  protected function preprocess(array $config) {
    return ArrayManipulator::expandFromDotNotatedKeys(ArrayManipulator::flattenToDotNotatedKeys($config));
  }

}
