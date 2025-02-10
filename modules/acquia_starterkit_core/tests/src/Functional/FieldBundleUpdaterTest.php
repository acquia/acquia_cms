<?php

declare(strict_types=1);

namespace Drupal\tests\acquia_starterkit_core\Functional;

use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\acquia_starterkit_core\Traits\DirectoryHelperTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests the FieldBundleUpdater class.
 *
 * @coversDefaultClass \Drupal\acquia_starterkit_core\EntityOperations\FieldBundleUpdater
 * @group acquia_starterkit_core
 */
class FieldBundleUpdaterTest extends BrowserTestBase {

  use RecipeTestTrait;
  use DirectoryHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media', 'taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Holds the configuration name.
   *
   * @var string
   */
  protected string $configName;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->configName = 'field.field.node.article.field_tags';
  }

  /**
   * Tests adding a bundle to a field on recipe apply.
   */
  public function testAddBundleToFieldOnRecipeApply(): void {
    $target_bundles = ["categories" => "categories", "blogs" => "blogs"];
    $recipe_path = $this->prepareRecipe("core/recipes/article_tags");

    $this->createVocabularies($target_bundles);

    $this->alterRecipe($recipe_path, function (array $data) use ($target_bundles): array {
      $data['config']['actions'][$this->configName]['setThirdPartySettings'][] = [
        'module' => 'acquia_starterkit_core',
        'key' => 'target_bundles',
        'value' => ["add" => $target_bundles],
      ];
      return $data;
    });

    $this->applyAndVerifyRecipe($recipe_path, $target_bundles);
  }

  /**
   * Tests bundle is added to field by config import after recipe applied.
   */
  public function testAddBundleToFieldByConfigImportOnRecipeApply(): void {
    $target_bundles = ["categories" => "categories", "blogs" => "blogs"];
    $recipe_path = $this->prepareRecipe("core/recipes/article_tags");

    $this->updateConfig("$recipe_path/config/" . $this->configName . ".yml", function ($data) use ($target_bundles) {
      $data['third_party_settings']['acquia_starterkit_core']['target_bundles']['add'] = $target_bundles;
      return $data;
    });

    $this->createVocabularies($target_bundles);
    $this->applyAndVerifyRecipe($recipe_path, $target_bundles);
  }

  /**
   * Creates taxonomy vocabularies based on target bundles.
   *
   * @param array $target_bundles
   *   An array of bundle names.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createVocabularies(array $target_bundles): void {
    $taxonomy_vocab = $this->container->get('entity_type.manager')->getStorage('taxonomy_vocabulary');
    foreach ($target_bundles as $target_bundle) {
      $taxonomy_vocab->create(['name' => $this->randomString(), 'vid' => $target_bundle])->save();
    }
  }

  /**
   * Prepares a recipe for testing.
   *
   * @param string $source_path
   *   Path to the source recipe.
   */
  private function prepareRecipe(string $source_path): string {
    // Clone a recipe from the source path to a unique destination
    // and then alter the recipe data by adding necessary configurations.
    $destination_path = $this->cloneDirectory(DRUPAL_ROOT . "/$source_path", $this->siteDirectory . DIRECTORY_SEPARATOR . uniqid());

    $this->alterRecipe($destination_path, function (array $data): array {
      $data['install'][] = 'acquia_starterkit_core';

      // This is done because we are copying the recipe to another path.
      // So we need to update the path in the recipe.
      $data['recipes'] = [
        "core/recipes/article_content_type",
        "core/recipes/tags_taxonomy",
      ];
      return $data;
    });

    return $destination_path;
  }

  /**
   * Updates the given configuration.
   *
   * @param string $config_path
   *   Given configuration path.
   * @param callable $callback
   *   Callback function.
   */
  private function updateConfig(string $config_path, callable $callback): void {
    $data = Yaml::parseFile($config_path);
    $data = $callback($data);
    file_put_contents($config_path, Yaml::dump($data));
  }

  /**
   * Apply the given recipe and verify the changes.
   *
   * @param string $recipe_path
   *   Given recipe path.
   * @param array $target_bundles
   *   An array of bundle names.
   */
  private function applyAndVerifyRecipe(string $recipe_path, array $target_bundles): void {
    $this->applyRecipe($recipe_path);

    $config = $this->container->get("config.factory")->getEditable($this->configName);

    // Combining tags with target bundles because they already exist in config.
    $expected = array_merge(["tags" => "tags"], $target_bundles);

    $this->assertSame($expected, $config->get('settings.handler_settings.target_bundles'));
  }

}
