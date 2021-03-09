<?php

namespace Drupal\acquia_cms_development\Config;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Implements configuration overrides for internal development.
 *
 * This class implements a configuration override to use our internal Solr core
 * for Acquia Search. This is for development and testing purposes only; this
 * code is not shipped with Acquia CMS.
 */
final class ConfigOverrider implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $name = 'acquia_search.settings';
    if (AcquiaDrupalEnvironmentDetector::isAhIdeEnv() && in_array($name, $names, TRUE)) {
      $overrides[$name]['override_search_core'] = 'BGVZ-196143.dev.orionacms';
    }

    // Override system performance on IDE and local environment.
    $name = 'system.performance';
    if (in_array($name, $names, TRUE)
      && (AcquiaDrupalEnvironmentDetector::isAhIdeEnv() || AcquiaDrupalEnvironmentDetector::isLocalEnv())) {
      $overrides[$name]['cache']['page']['max_age'] = 0;
      $overrides[$name]['css']['preprocess'] = FALSE;
      $overrides[$name]['js']['preprocess'] = FALSE;
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'AcmsExampleOverrider';
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
    // This is technically violating the interface contract for this method, but
    // there is (unusual) precedent for this pattern in core. Since this config
    // overrider only exists for internal development, it's probably fine.
    // @see \Drupal\Core\Installer\ConfigOverride::createConfigObject()
    return NULL;
  }

}
