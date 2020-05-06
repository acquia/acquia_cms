<?php

namespace Drupal\acquia_cms\Config;

use Acquia\EnvironmentDetector\AcquiaEnvironmentDetector as EnvironmentDetector;
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

    if (EnvironmentDetector::isAhEnv()) {
      $overrides['environment_indicator.indicator']['name'] = ucfirst($_ENV['AH_SITE_ENVIRONMENT']);

      if (EnvironmentDetector::isAhDevEnv()) {
        $overrides['environment_indicator.indicator']['bg_color'] = '#33aa3c';
      }

      if (EnvironmentDetector::isAhStageEnv()) {
        $overrides['environment_indicator.indicator']['bg_color'] = '#ffBB00';
      }

      if (EnvironmentDetector::isAhProdEnv()) {
        $overrides['environment_indicator.indicator']['bg_color'] = '#aa3333';
      }
    }
    else {
      $overrides['environment_indicator.indicator']['name'] = 'Local';
      $overrides['environment_indicator.indicator']['bg_color'] = '#3363aa';
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
