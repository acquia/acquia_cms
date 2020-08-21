<?php

/**
 * @file
 * Auto-connect to the correct Acquia credentials file.
 */

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;

/**
 * Site path.
 *
 * @var $site_path
 * This is always set and exposed by the Drupal Kernel.
 */
// phpcs:ignore
if (AcquiaDrupalEnvironmentDetector::isAhEnv()) {
  if (AcquiaDrupalEnvironmentDetector::isAhEnv()) {
    $ah_group = AcquiaDrupalEnvironmentDetector::getAhGroup();
    if (!AcquiaDrupalEnvironmentDetector::isAcsfEnv()) {
      require "/var/www/site-php/$ah_group/$ah_group-settings.inc";
    }
  }
}

// Use memcache as cache backend if Acquia configuration is present.
$repo_root = dirname(DRUPAL_ROOT);
$memcacheSettingsFile = $repo_root . '/vendor/acquia/memcache-settings/memcache.settings.php';
if (file_exists($memcacheSettingsFile && getenv('ENABLE_MEMCACHED'))) {
  // phpcs:ignore
  require $memcacheSettingsFile;
}
