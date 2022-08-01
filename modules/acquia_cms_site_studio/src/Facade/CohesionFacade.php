<?php

namespace Drupal\acquia_cms_site_studio\Facade;

use Drupal\cohesion_sync\PackagerManager;
use Drupal\cohesion_sync\Services\PackageImportHandler;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

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
   * The uuid service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * The uuid service.
   *
   * @var \Drupal\cohesion_sync\Services\PackageImportHandler
   */
  protected $packageImportHandler;

  /**
   * CohesionFacade constructor.
   *
   * @param \Drupal\cohesion_sync\PackagerManager $packager
   *   The Cohesion sync packager manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The uuid service.
   * @param \Drupal\cohesion_sync\Services\PackageImportHandler $package_import_handler
   *   The uuid service.
   */
  public function __construct(PackagerManager $packager,
  ModuleHandlerInterface $module_handler,
  UuidInterface $uuid,
  PackageImportHandler $package_import_handler) {
    $this->packager = $packager;
    $this->moduleHandler = $module_handler;
    $this->uuidGenerator = $uuid;
    $this->packageImportHandler = $package_import_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cohesion_sync.packager'),
      $container->get('module_handler'),
      $container->get('uuid'),
      $container->get('cohesion_sync.package_import_handler')
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
  public function importPackage(string $package, bool $no_rebuild = FALSE): array {
    // Prepare to import the package. This code is delicate because it was
    // basically written by rooting around in Cohesion's internals. So be
    // extremely careful when changing it.
    // @see \Drupal\cohesion_sync\Form\ImportFileForm::submitForm()
    // @see \Drupal\cohesion_sync\Drush\CommandHelpers::import()
    // Use validatePackageBatch method as per site studio 6.5.0
    $store_key = 'drush_sync_validation' . $this->uuidGenerator->generate();
    $validate_package_operations = $this->packager->validatePackageBatch($package, $store_key);
    // Basically, overwrite everything without validating. This is equivalent
    // to passing the --overwrite-all and --force options to the 'sync:import'
    // Drush command.
    $batch_operations[] = [
      '\Drupal\cohesion_sync\Controller\BatchImportController::setImportBatch',
      [$package, $store_key, TRUE, FALSE, TRUE, $no_rebuild, FALSE],
    ];
    return array_merge($validate_package_operations, $batch_operations);
  }

  /**
   * Returns a list of all installed modules.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   A list of all installed modules. The acquia_cms profile and its modules
   *   will be the last items in the list.
   */
  private function getSortedModules() : array {
    $module_list = $this->moduleHandler->getModuleList();
    $acms_module_list = [];

    foreach ($module_list as $name => $extension) {
      if ('acquia_cms' === $name) {
        // Ensure the Acquia CMS Profile is the first ACMS extension.
        $acms_module_list = [$name => $extension] + $acms_module_list;
        unset($module_list[$name]);
      }
      elseif (stripos($name, 'acquia_cms') === 0) {
        // Add any other ACMS modules to the array.
        $acms_module_list[$name] = $extension;
        unset($module_list[$name]);
      }
    }

    return $module_list + $acms_module_list;
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
  public static function batchFinishedCallback(bool $success, array $results, array $operations) {
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
   */
  public function getAllOperations() : void {
    $package_list = [];
    $modules = $this->getSortedModules();
    foreach ($modules as $module) {
      $module_path = $module->getPath();
      $package_list_path = $module_path . COHESION_SYNC_DEFAULT_MODULE_PACKAGES;
      if (file_exists($package_list_path)) {
        $package = $this->readPackageList($package_list_path);
        $package_list = array_merge($package_list, $package);
      }
    }
    if ($package_list) {
      $this->packageImportHandler->importPackagesFromArray($package_list);
    }
  }

  /**
   * Reads Package List file from provided path.
   *
   * @param string $package_list_path
   *   Path to package list file.
   *
   * @return array
   *   Package list.
   */
  public function readPackageList(string $package_list_path): array {

    if (file_exists($package_list_path)) {
      $package_list = Yaml::parse(file_get_contents($package_list_path));
      if ($package_list === NULL) {
        throw new PackageListEmptyOrMissing($package_list_path);
      }
    }

    return $package_list;
  }

}
