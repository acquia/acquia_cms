<?php

/**
 * @file
 * Controls configuration management settings.
 */

use Acquia\Drupal\RecommendedSettings\Helpers\EnvironmentDetector;

/**
 * Override config directories.
 *
 * BLT makes the assumption that, if using multisite, the default configuration
 * directory should be shared between all multi-sites, and each multisite will
 * override this selectively using configuration splits. However, some
 * applications may prefer to manage the configuration for each multisite
 * completely separately. If this is the case, they can set
 * $blt_override_config_directories to FALSE and
 * $settings['config_sync_directory'] = $dir . "/config/$site_dir" in
 * settings.php, and we will not overwrite it.
 */


/**
 * Site path.
 *
 * @var string $site_path
 * This is always set and exposed by the Drupal Kernel.
 */
 // phpcs:ignore
 $site_name = EnvironmentDetector::getSiteName($site_path);
// phpcs:ignore
// Config sync settings.
$settings['config_sync_directory'] = "../config/" . $site_name . "/sync";
// Site Studio sync settings.
$settings['site_studio_sync'] = "../config/" . $site_name . "/sitestudio";

$split_filename_prefix = 'config_split.config_split';

/**
 * Set environment splits.
 */
$split_envs = EnvironmentDetector::getEnvironments();
foreach ($split_envs as $split_env => $status) {
  $config["$split_filename_prefix.$split_env"]['status'] = $status;
}

// phpcs:ignore
$config["$split_filename_prefix.$site_name"]['status'] = TRUE;

/**
 * Set multisite split.
 */
// Set acsf site split if explicit global exists.
global $_acsf_site_name;
if (isset($_acsf_site_name)) {
  $config["$split_filename_prefix.$_acsf_site_name"]['status'] = TRUE;
}
