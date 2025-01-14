<?php

namespace Drupal\acquia_cms_search\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigInstaller;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Recipe\RecipeAppliedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Finder\Finder;

/**
 * Import search configurations from related recipes.
 *
 * @package Drupal\acquia_cms_search\EventSubscriber
 */
class RecipeConfigImporter implements EventSubscriberInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigInstaller
   */
  protected $configInstaller;

  /**
   * Constructs a new RecipeConfigImporter object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
  //   * @param \Drupal\Core\Config\ConfigInstaller $config_installer
  //   *   The config installer service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    //    ConfigInstaller $config_installer,
  ) {
    $this->configFactory = $config_factory;
    //    $this->configInstaller = $config_installer;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      RecipeAppliedEvent::class => 'onRecipeApply',
    ];
  }

  /**
   * Import the search related config from related recipe.
   *
   * @param \Drupal\Core\Recipe\RecipeAppliedEvent $event
   *   The recipe applied event.
   */
  public function onRecipeApply(RecipeAppliedEvent $event): void {
    $recipe_name = $this->getRecipeName($event->recipe->path);
    if ($recipe_name == 'acquia_starterkit_search') {
      //      $this->configInstaller->installOptionalConfig(NULL, ['module' => 'acquia_cms_search']);
      \Drupal::service('config.installer')->installOptionalConfig(NULL, [
        'module' => 'acquia_cms_search'
      ]);
      $this->importSearchConfig();
    }
    elseif (str_starts_with($recipe_name, 'acquia_starterkit_')) {
      $this->importConfigFromRecipe($event->recipe->path);
    }
  }

  /**
   * Extracts the recipe name from the given path.
   *
   * @param string $path
   *   The path to the recipe.
   *
   * @return string
   *   The extracted recipe name.
   */
  private function getRecipeName(string $path): string {
    $exploded = explode('/', $path);
    return end($exploded);
  }

  /**
   * Imports search configuration if it exists.
   */
  private function importSearchConfig(): void {
    $recipes = $this->configFactory->get('acquia_starterkit_core.settings')->get('recipes_applied') ?? [];
    foreach ($recipes as $recipe) {
      $recipe_path = $this->getRecipePathByName($recipe);
      $this->importConfigFromRecipe($recipe_path . '/' . $recipe);
    }
  }

  /**
   * Imports configuration from the specified recipe path.
   *
   * @param string $recipe_path
   *   The path to the recipe.
   */
  private function importConfigFromRecipe(string $recipe_path): void {
    $search_config_path = $recipe_path . '/search';
    $search_config_path_install = $recipe_path . '/search/install';
    $this->importConfigFromPath($search_config_path_install);
    $current_theme = $this->configFactory->get('system.theme')->get('default');
    $search_config_path_current_theme = $search_config_path . '/' . $current_theme;
    $this->importConfigFromPath($search_config_path_current_theme);
  }

  /**
   * Imports configuration from the specified path.
   *
   * @param string $config_path
   *   The path to the recipe.
   */
  private function importConfigFromPath(string $config_path): void {
    $config_source = new FileStorage($config_path);
    //    $this->configInstaller->installOptionalConfig($config_source);
    \Drupal::service('config.installer')->installOptionalConfig($config_source);
  }

  /**
   * Gets the path to the recipe by its name.
   *
   * @param string $recipe_name
   *   The name of the recipe.
   *
   * @return string|null
   *   The path to the recipe, or null if not found.
   */
  private function getRecipePathByName(string $recipe_name): ?string {
    $finder = new Finder();
    $finder->directories()->in(DRUPAL_ROOT)->name($recipe_name);

    foreach ($finder as $file) {
      return $file->getPath();
    }

    return NULL;
  }

}
