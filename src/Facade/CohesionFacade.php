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
   * @param bool $no_rebuild
   *   Whether rebuild operation should execute or not.
   *
   * @return array
   *   The batch operations.
   *
   * @throws \Exception
   */
  public function importPackage(string $package, $no_rebuild = FALSE): array {
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

    $batch_operations = [];

    $batch_operations[] = [
      '_display_package_import_operation',
      [$package],
    ];
    $operations = $this->packager->applyBatchYamlPackageStream($package, $action_data, $no_rebuild);
    $batch_operations = \array_merge($batch_operations, $operations);

    return $batch_operations;
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

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Status of batch process.
   * @param array $results
   *   Result of the operations performed.
   * @param array $operations
   *   Operations performed in the batch process.
   */
  public static function batchFinishedCallback($success, array $results, array $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      \Drupal::messenger()->addMessage(t('The import succeeded. @count tasks completed.', ['@count' => count($results)]));
    }
    else {
      \Drupal::messenger()->addMessage(t('Finished with an error.'));
    }
  }

  /**
   * Get all required operations to import site studio packages of Acquia CMS.
   *
   * @param bool $no_rebuild
   *   Whether rebuild operation should execute or not.
   *
   * @return array
   *   All the operations.
   */
  public function getAllOperations(bool $no_rebuild = FALSE) : array {
    $operations = [];
    foreach ($this->getAllPackages() as $package) {
      $operations = array_merge($operations, $this->importPackage($package, $no_rebuild));
    }
    return $operations;
  }

}
