<?php

namespace Drupal\acquia_cms_example\Config;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Acquia CMS Example configuration override for the search core.
 *
 * This class implements a configuration ovverride for the Acquia Search SOLR
 * module to implement a search for for Cloud IDEs.
 */
final class AcquiaCmsExampleConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    if (AcquiaDrupalEnvironmentDetector::isAhIdeEnv()) {
      // Acquia Search Solr Overrides.
      $overrides['acquia_search_solr.settings']['override_search_core'] = 'BGVZ-196143.dev.orionacms';
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
    return NULL;
  }

}
