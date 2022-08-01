<?php

namespace Drupal\acquia_cms_tour\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeInstallerInterface;

/**
 * Defines a service that toggle modules based on environment.
 */
class StarterKitService {

  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * The theme installer.
   *
   * @var \Drupal\Core\Extension\ThemeInstallerInterface
   */
  protected $themeInstaller;

  /**
   * The config factory service object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Constructs a new AcmsService object.
   *
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The ModuleHandlerInterface.
   * @param \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer
   *   The ThemeInstallerInterface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   */
  public function __construct(
  ModuleInstallerInterface $module_installer,
  ThemeInstallerInterface $theme_installer,
  ConfigFactoryInterface $config_factory,
  ModuleExtensionList $extension_list_module) {
    $this->moduleInstaller = $module_installer;
    $this->themeInstaller = $theme_installer;
    $this->configFactory = $config_factory;
    $this->moduleExtensionList = $extension_list_module;
  }

  /**
   * Handler for enabling modules.
   *
   * @param string $starter_kit
   *   Variable holding the starter kit selected.
   * @param string $demo_question
   *   Variable holding the demo question option selected.
   * @param string $content_model
   *   Variable holding the content model option selected.
   */
  public function enableModules(string $starter_kit, string $demo_question = NULL, string $content_model = NULL) {
    $modulesAndThemes = $this->getModulesAndThemes($starter_kit, $demo_question, $content_model);
    if (!empty($modulesAndThemes['enableModules'])) {
      $this->moduleInstaller->install($modulesAndThemes['enableModules']);
    }
    foreach ($modulesAndThemes['enableThemes'] as $theme) {
      $this->themeInstaller->install([$theme]);
    }
    $this->configFactory
      ->getEditable('system.theme')
      ->set('default', $modulesAndThemes['enableThemes']['default'])
      ->save();
    $this->configFactory
      ->getEditable('system.theme')
      ->set('admin', $modulesAndThemes['enableThemes']['admin'])
      ->save();
  }

  /**
   * Handler for enabling modules.
   *
   * @param string $starter_kit
   *   Variable holding the starter kit selected.
   * @param string $demo_question
   *   Variable holding the demo question option selected.
   * @param string $content_model
   *   Variable holding the content model option selected.
   */
  public function getModulesAndThemes(string $starter_kit, string $demo_question = NULL, string $content_model = NULL) {
    $enableModules = $enableThemes = [];
    switch ($starter_kit) {
      case 'acquia_cms_enterprise_low_code':
        $enableModules = [
          'acquia_cms_page',
          'acquia_cms_search',
          'acquia_cms_site_studio',
          'acquia_cms_toolbar',
          'acquia_cms_tour',
        ];
        $enableThemes = [
          'admin'   => 'acquia_claro',
          'default' => 'cohesion_theme',
        ];
        break;

      case 'acquia_cms_community':
        $enableModules = [
          'acquia_cms_search',
          'acquia_cms_toolbar',
          'acquia_cms_tour',
        ];
        $enableThemes = [
          'admin'   => 'acquia_claro',
          'default' => 'olivero',
        ];
        break;

      case 'acquia_cms_headless':
        $enableModules = [
          'acquia_cms_headless',
          'acquia_cms_search',
          'acquia_cms_toolbar',
          'acquia_cms_tour',
        ];
        $enableThemes = [
          'admin'   => 'acquia_claro',
          'default' => 'olivero',
        ];
        break;

      default:
        $enableThemes = [
          'admin'   => 'acquia_claro',
          'default' => 'olivero',
        ];
        $enableModules = [
          'acquia_cms_search',
          'acquia_cms_toolbar',
          'acquia_cms_tour',
        ];
    }
    if ($demo_question == 'Yes') {
      $enableModules = array_merge(
        $enableModules, ['acquia_cms_starter'],
      );
    }
    elseif ($content_model == 'Yes') {
      $enableModules = array_merge(
        $enableModules, [
          'acquia_cms_article',
          'acquia_cms_page',
          'acquia_cms_event',
        ],
      );
    }
    return [
      'enableModules' => $enableModules,
      'enableThemes' => $enableThemes,
    ];
  }

  /**
   * Handler for missing modules.
   *
   * @param string $starter_kit
   *   Variable holding the starter kit selected.
   * @param string $demo_question
   *   Variable holding the demo question option selected.
   * @param string $content_model
   *   Variable holding the content model option selected.
   */
  public function getMissingModules(string $starter_kit, string $demo_question = NULL, string $content_model = NULL) {
    $modulesAndThemes = $this->getModulesAndThemes($starter_kit, $demo_question, $content_model);
    $modules = $modulesAndThemes['enableModules'];
    $moduleList = array_keys($this->moduleExtensionList->getList());
    $missingModules = implode(', ', array_diff($modules, $moduleList)) ?? '';
    return $missingModules;
  }

  /**
   * Handler for Missing Modules Command modules.
   *
   * @param string $missing_modules
   *   Variable holding the starter kit selected.
   */
  public function getMissingModulesCommand(string $missing_modules) {
    if ($missing_modules) {
      $missing_modules = 'drupal/' . $missing_modules;
      $missing_modules = str_replace(', ', ' drupal/', $missing_modules);
    }
    return $missing_modules;
  }

}
