<?php

namespace Drupal\acquia_cms\Config;

use Acquia\Blt\Robo\Common\EnvironmentDetector;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * ACMS configuration override.
 */
class ACMSConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    // Environment_indicator settings.
    $overrides['environment_indicator_overwrite'] = TRUE;
    $overrides['environment_indicator.indicator']['fg_color'] = '#ffffff';

    if (EnvironmentDetector::isLocalEnv()) {
      $overrides['environment_indicator.indicator']['name'] = 'Local';
      $overrides['environment_indicator.indicator']['bg_color'] = '#3363aa';
    }

    if (EnvironmentDetector::isAhEnv()) {
      $overrides['environment_indicator.indicator']['name'] = ucfirst($_ENV['AH_SITE_ENVIRONMENT']);

      if (EnvironmentDetector::isDevEnv()) {
        $overrides['environment_indicator.indicator']['bg_color'] = '#33aa3c';
      }

      if (EnvironmentDetector::isStageEnv()) {
        $overrides['environment_indicator.indicator']['bg_color'] = '#ffBB00';
      }

      if (EnvironmentDetector::isProdEnv()) {
        $overrides['environment_indicator.indicator']['bg_color'] = '#aa3333';
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'ACMSOverrider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
