<?php

namespace Drupal\Tests\acquia_cms_common\Traits;

/**
 * Provides a configuration value.
 */
trait ConfigurationTraits {

  /**
   * Check config key exists.
   *
   * @param string $field_config_name
   *   Field config name.
   * @param string $config_key
   *   Configuration key.
   *
   * @return bool
   *   TRUE/FALSE
   */
  protected function configKeyExists(string $field_config_name, string $config_key): bool {
    $output = FALSE;
    $field_config = \Drupal::configFactory()->getEditable($field_config_name);
    if ($field_config) {
      switch ($config_key) {
        case 'dependencies.config':
          if (in_array('media.type.acquia_dam_image_asset', $field_config->get($config_key))) {
            $output = TRUE;
          }
          break;

        case 'settings.handler_settings.target_bundles':
          if (in_array('acquia_dam_image_asset', $field_config->get($config_key))) {
            $output = TRUE;
          }
          break;
      }
    }

    return $output;
  }

}
