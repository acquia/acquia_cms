<?php

namespace Drupal\acquia_cms\Facade;

use Drupal\cohesion_sync\PackagerManager;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a facade for configuring and interacting with Cohesion.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class CohesionFacade implements ContainerInjectionInterface {

  /**
   * The Cohesion sync packager manager service.
   *
   * @var \Drupal\cohesion_sync\PackagerManager
   */
  private $packager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * CohesionFacade constructor.
   *
   * @param \Drupal\cohesion_sync\PackagerManager $packager
   *   The Cohesion sync packager manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(PackagerManager $packager, ModuleHandlerInterface $module_handler) {
    $this->packager = $packager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cohesion_sync.packager'),
      $container->get('module_handler')
    );
  }

  /**
   * Imports a single sync package.
   *
   * @param string $package
   *   The path to the sync package, relative to the Drupal root.
   * @param bool $set_batch
   *   If TRUE,  batch will be created and set immediately; otherwise;
   *   operations for the batch will be return.
   *
   * @return array
   *   The batch operations.
   *
   * @throws \Exception
   */
  public function importPackage(string $package, bool $set_batch = FALSE): array {
    // Prepare to import the package. This code is delicate because it was
    // basically written by rooting around in Cohesion's internals. So be
    // extremely careful when changing it.
    // @see \Drupal\cohesion_sync\Form\ImportFileForm::submitForm()
    // @see \Drupal\cohesion_sync\Drush\CommandHelpers::import()
    $action_data = $this->packager->validateYamlPackageStream($package);

    // Basically, overwrite everything without validating. This is equivalent
    // to passing the --overwrite-all and --force options to the 'sync:import'
    // Drush command.
    foreach ($action_data as &$action) {
      $action['entry_action_state'] = ENTRY_EXISTING_OVERWRITTEN;
    }

    // Get the batch operations for the sync import.
    if ($set_batch) {
      $operations = $this->packager->applyBatchYamlPackageStream($package, $action_data);
      $batch = [
        'title' => $this->t('Importing configuration.'),
        'finished' => '\Drupal\cohesion_sync\Controller\BatchImportController::batchFinishedCallback',
        'operations' => $operations,
      ];
      batch_set($batch);
    }
    else {
      $batch_operations = [];
      $batch_operations = array_merge($batch_operations, $this->packager->applyBatchYamlPackageStream($package, $action_data));
      $batch_operations[] = [
        '_acquia_cms_install_ui_kit_report_callback',
        [$package],
      ];
      return $batch_operations;
    }

  }

  /**
   * Returns a list of all available sync packages.
   *
   * This will also include the big UI kit shipped during development of Acquia
   * CMS (misc/ui-kit.package.yml), at the end of the list.
   *
   * @return string[]
   *   An array of sync package paths, relative to the Drupal root.
   */
  public function getAllPackages() : array {
    $packages = [];
    foreach ($this->getSortedModules() as $module) {
      $packages = array_merge($packages, $this->getPackagesFromExtension($module));
    }
    // @todo This line should be deleted when we are no longer shipping the big
    // UI kit package.
    $packages[] = $this->moduleHandler->getModule('acquia_cms')->getPath() . '/misc/ui-kit.package.yml';

    return $packages;
  }

  /**
   * Returns a list of all sync packages shipped with an extension.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   The extension to scan.
   *
   * @return string[]
   *   An array of sync package paths, relative to the Drupal root.
   */
  public function getPackagesFromExtension(Extension $extension) : array {
    $dir = $extension->getPath();

    $list = "$dir/config/dx8/packages.yml";
    if (file_exists($list)) {
      $list = file_get_contents($list);

      $map = function (string $package) use ($dir) {
        return "$dir/$package";
      };
      return array_map($map, Yaml::decode($list));
    }
    return [];
  }

  /**
   * Returns a list of all installed modules.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   A list of all installed modules. The acquia_cms profile will be the last
   *   item in the list.
   */
  private function getSortedModules() : array {
    $modules = $this->moduleHandler->getModuleList();

    $profile = $modules['acquia_cms'];
    unset($modules['acquia_cms']);
    $modules['acquia_cms'] = $profile;

    return $modules;
  }

}
