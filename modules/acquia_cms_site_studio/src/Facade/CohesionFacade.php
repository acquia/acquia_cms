<?php

namespace Drupal\acquia_cms_site_studio\Facade;

use Drupal\cohesion_sync\Exception\PackageListEmptyOrMissing;
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
   * Import site studio packages of Acquia CMS modules.
   *
   * @param array $package_list
   *   Array of packages.
   *
   * @return bool
   *   TRUE if batch was set successfully.
   *
   * @throws \Exception
   */
  public function importSiteStudioPackages(array $package_list = []) : bool {
    if (empty($package_list)) {
      $modules = $this->moduleHandler->getModuleList();
      $package_list = $this->buildPackageList($modules);
    }
    if (!empty($package_list)) {
      return $this->packageImportHandler->importPackagesFromArray($package_list);
    }
    return FALSE;
  }

  /**
   * Build site studio package for modules.
   *
   * @param array $modules
   *   The modules array.
   *
   * @return array
   *   This site studio packages.
   */
  public function buildPackageList(array $modules): array {
    $package_list = [];
    foreach ($modules as $module) {
      $module_path = $module->getPath();
      $package_list_path = $module_path . COHESION_SYNC_DEFAULT_MODULE_PACKAGES;
      if ($package = $this->readPackageList($package_list_path)) {
        $package_list = array_merge($package_list, $package);
      }
    }
    return $package_list;
  }

  /**
   * Reads Package List files from provided path.
   *
   * @param string $package_list_path
   *   Path to package list file.
   *
   * @return array
   *   Package list.
   */
  public function readPackageList(string $package_list_path): array {
    $package_list = [];
    if (file_exists($package_list_path)) {
      $package_list = Yaml::parse(file_get_contents($package_list_path));
      if ($package_list === NULL) {
        throw new PackageListEmptyOrMissing($package_list_path);
      }
    }

    return $package_list;
  }

}
