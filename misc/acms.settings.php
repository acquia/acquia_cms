<?php

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;

if (AcquiaDrupalEnvironmentDetector::isAhEnv()) {
  if ( AcquiaDrupalEnvironmentDetector::isAhEnv()) {
    $ah_group = AcquiaDrupalEnvironmentDetector::getAhGroup();
    if (! AcquiaDrupalEnvironmentDetector::isAcsfEnv()) {
      if ($site_name == 'default') {
        $settings_files[] = "/var/www/site-php/$ah_group/$ah_group-settings.inc";
      } else {
        // Acquia Cloud does not support periods in db names.
        $safe_site_name = str_replace('.', '_', $site_name);
        $settings_files[] = "/var/www/site-php/$ah_group/$safe_site_name-settings.inc";
      }
    }
  }

  /**
   * Use memcache as cache backend if Acquia configuration is present.
   */
  $repo_root = dirname(DRUPAL_ROOT);
  $memcacheSettingsFile = $repo_root . '/vendor/acquia/memcache-settings/memcache.settings.php';
  if (file_exists($memcacheSettingsFile && getenv('ENABLE_MEMCACHED'))) {
    // phpcs:ignore
    require $memcacheSettingsFile;
  }
}
