<?php

namespace Drupal\acquia_cms_site_studio\Services;

use Drupal\cohesion_sync\Exception\PackageSourceMissingPropertiesException;
use Drupal\cohesion_sync\Services\PackageSourceServiceInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\Exception\DirectoryNotReadyException;
use Drupal\Core\Extension\ExtensionPathResolver;

/**
 * Default Recipe Package service.
 */
class DefaultRecipePackage implements PackageSourceServiceInterface {

  const SUPPORTED_TYPE = 'default_recipe_package';
  const REQUIRED_PROPERTIES = ['recipe_name', 'path'];

  /**
   * ModuleHandler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Factory for getting extension lists by type.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * DefaultModulePackage constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler service.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extensionPathResolver
   *   Factory for getting extension lists by type.
   * @param \Drupa\Core\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    ModuleHandlerInterface $moduleHandler,
    ExtensionPathResolver $extensionPathResolver,
    ConfigFactoryInterface $configFactory,
  ) {
    $this->moduleHandler = $moduleHandler;
    $this->extensionPathResolver = $extensionPathResolver;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function supportedType(string $type): bool {
    return $type === self::SUPPORTED_TYPE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedType(): string {
    return self::SUPPORTED_TYPE;
  }

  /**
   * Handles default recipe packages.
   *
   * No downloading/file moving actions are required, so we're
   * checking for correct metadata values and returning sync dir path.
   *
   * @param array $sourceMetadata
   *   Source metadata.
   *
   * @return string
   *   Path to default package directory in recipe.
   *
   * @throws \Exception
   *   Thrown if source metadata values are missing.
   */
  public function preparePackage(array $sourceMetadata): string {
    $dependencies = $sourceMetadata['dependencies'] ?? [];
    $recipe_applied = $this->configFactory->get('acquia_starterkit_core.settings')->get('recipes_applied');
    if ($dependencies) {
      foreach ($dependencies['recipe'] as $dependency) {
        if (!$this->recipeExist($dependency) || !in_array($sourceMetadata['recipe_name'], $recipe_applied)) {
          return "";
        }
      }
    }
    $this->validateMetadata($sourceMetadata);

    $recipe_path = $this->getRecipePath($sourceMetadata['recipe_name']);
    $package_path = $sourceMetadata['path'];

    return $recipe_path . '/' . $package_path;
  }

  /**
   * Validates Source metadata.
   *
   * @param array $sourceMetadata
   *   Metadata passed to Source service.
   *
   * @return void
   */
  protected function validateMetadata(array $sourceMetadata): void {
    $missingProperties = array_diff(self::REQUIRED_PROPERTIES, array_keys($sourceMetadata));
    if (!empty($missingProperties)) {
      throw new PackageSourceMissingPropertiesException(self::SUPPORTED_TYPE, $missingProperties, self::REQUIRED_PROPERTIES);
    }

    if (!$this->recipeExist($sourceMetadata['recipe_name'])) {
      throw new MissingDependencyException(sprintf('Missing recipe: %s', $sourceMetadata['recipe_name']));
    }

    $recipePath = $this->getRecipePath($sourceMetadata['recipe_name']);
    $packagePath = $recipePath . '/' . $sourceMetadata['path'];
    if (!is_dir($packagePath) || !is_readable($packagePath)) {
      throw new DirectoryNotReadyException(sprintf('Directory not found or not readable: %s', $packagePath));
    }
  }

  /**
   * Checks if a recipe exists.
   *
   * @param string $recipeName
   *   The name of the recipe.
   *
   * @return bool
   *   TRUE if the recipe exists, FALSE otherwise.
   */
  protected function recipeExist(string $recipe_name): bool {
    $recipe_path = $this->getRecipePath($recipe_name) . '/recipe.yml';
    return file_exists($recipe_path);
  }

  /**
   * Gets the path to the recipe.
   *
   * @param string $recipeName
   *   The name of the recipe.
   *
   * @return string
   *   The path to the recipe.
   */
  public function getRecipePath(string $recipe_name): string {
    return \Drupal::root() . '/recipes/' . $recipe_name;
  }

}
