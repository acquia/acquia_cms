<?php

namespace Drupal\acquia_cms_common\Services;

use Drupal\cohesion\Drush\DX8CommandHelpers;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Defines a service for ACMS.
 */
class AcmsUtilityService {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new AcmsService object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The ModuleHandlerInterface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, ConfigFactoryInterface $config_factory) {
    $this->moduleHandler = $moduleHandler;
    $this->configFactory = $config_factory;
  }

  /**
   * Fetch acquia cms profile with list of enabled modules of ACMS.
   */
  public function getAcquiaCmsProfileModuleList(): array {
    $profile_modules = $this->moduleHandler->getModuleList();
    return array_filter($profile_modules, function ($key) {
      return str_starts_with($key, 'acquia_cms');
    }, ARRAY_FILTER_USE_KEY);
  }

  /**
   * Trigger site studio rebuild on demand.
   */
  public function rebuildSiteStudio() {
    // Forcefully clear the cache after site is installed otherwise site
    // studio fails to rebuild.
    drupal_flush_all_caches();
    // Below code ensures that drush batch process doesn't hang. Unset all the
    // earlier created batches so that drush_backend_batch_process() can run
    // without being stuck.
    // @see https://github.com/drush-ops/drush/issues/3773 for the issue.
    $batch = &batch_get();
    $batch = NULL;
    unset($batch);
    return DX8CommandHelpers::rebuild([
      'verbose' => '',
      'no-cache-clear' => FALSE,
    ]);
  }

  /**
   * Validates an array of config data that contains dependency information.
   *
   * Copied from Drupal/Core/Config/ConfigInstaller.php.
   *
   * @param string $config_name
   *   The name of the configuration object that is being validated.
   * @param array $data
   *   Configuration data.
   * @param array $enabled_extensions
   *   A list of all the currently enabled modules and themes.
   * @param array $all_config
   *   A list of all the active configuration names.
   *
   * @return bool
   *   TRUE if all dependencies are present, FALSE otherwise.
   */
  public function validateDependencies(string $config_name, array $data, array $enabled_extensions, array $all_config): bool {
    if (!isset($data['dependencies'])) {
      // Simple config or a config entity without dependencies.
      list($provider) = explode('.', $config_name, 2);
      return in_array($provider, $enabled_extensions, TRUE);
    }

    $missing = $this->getMissingDependencies($config_name, $data, $enabled_extensions, $all_config);
    return empty($missing);
  }

  /**
   * Returns an array of missing dependencies for a config object.
   *
   * Copied from Drupal/Core/Config/ConfigInstaller.php.
   *
   * @param string $config_name
   *   The name of the configuration object that is being validated.
   * @param array $data
   *   Configuration data.
   * @param array $enabled_extensions
   *   A list of all the currently enabled modules and themes.
   * @param array $all_config
   *   A list of all the active configuration names.
   *
   * @return array
   *   A list of missing config dependencies.
   */
  protected function getMissingDependencies(string $config_name, array $data, array $enabled_extensions, array $all_config): array {
    $missing = [];
    if (isset($data['dependencies'])) {
      list($provider) = explode('.', $config_name, 2);
      $all_dependencies = $data['dependencies'];

      // Ensure enforced dependencies are included.
      if (isset($all_dependencies['enforced'])) {
        $all_dependencies = array_merge($all_dependencies, $data['dependencies']['enforced']);
        unset($all_dependencies['enforced']);
      }
      // Ensure the configuration entity type provider is in the list of
      // dependencies.
      if (!isset($all_dependencies['module']) || !in_array($provider, $all_dependencies['module'])) {
        $all_dependencies['module'][] = $provider;
      }

      foreach ($all_dependencies as $type => $dependencies) {
        $list_to_check = [];
        switch ($type) {
          case 'module':
          case 'theme':
            $list_to_check = $enabled_extensions;
            break;

          case 'config':
            $list_to_check = $all_config;
            break;
        }
        if (!empty($list_to_check)) {
          $missing = array_merge($missing, array_diff($dependencies, $list_to_check));
        }
      }
    }

    return $missing;
  }

  /**
   * Gets the list of enabled extensions including both modules and themes.
   *
   * Copied from Drupal/Core/Config/ConfigInstaller.php.
   *
   * @return array
   *   A list of enabled extensions which includes both modules and themes.
   */
  public function getEnabledExtensions(): array {
    // Read enabled extensions directly from configuration to avoid circular
    // dependencies on ModuleHandler and ThemeHandler.
    $extension_config = $this->configFactory->get('core.extension');
    $enabled_extensions = (array) $extension_config->get('module');
    $enabled_extensions += (array) $extension_config->get('theme');
    // Core can provide configuration.
    $enabled_extensions['core'] = 'core';
    return array_keys($enabled_extensions);
  }

}
