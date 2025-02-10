<?php

declare(strict_types=1);

namespace Drupal\tests\acquia_starterkit_core\Functional;

use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\acquia_starterkit_core\Traits\ContentPermissionManagerTestTrait;
use Drupal\Tests\acquia_starterkit_core\Traits\DirectoryHelperTrait;
use Drupal\acquia_starterkit_core\EntityOperations\ContentPermissionManager;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests the ContentPermissionManager class.
 *
 * @coversDefaultClass \Drupal\acquia_starterkit_core\EntityOperations\ContentPermissionManager
 * @group acquia_starterkit_core
 */
class ContentPermissionManagerFunctionalTest extends BrowserTestBase {

  use RecipeTestTrait;
  use DirectoryHelperTrait;
  use ContentPermissionManagerTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Holds an object of ContentPermissionManager class.
   *
   * @var \Drupal\acquia_starterkit_core\EntityOperations\ContentPermissionManager
   */
  protected $contentPermissionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->contentPermissionManager = ContentPermissionManager::create($this->container);
  }

  /**
   * Tests permissions are granted though config actions after recipe applied.
   *
   * @param string $recipe_path
   *   Given recipe path to apply.
   * @param string $config_name
   *   Given entity type configuration.
   * @param array $roles_permissions
   *   An array of roles & permissions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @dataProvider assignRolePermissionsOnRecipeApplyDataProvider
   */
  public function testGrantPermissionsByConfigActionsOnRecipeApply(string $recipe_path, string $config_name, array $roles_permissions): void {
    $recipe_path = $this->cloneDirectory($recipe_path, $this->siteDirectory . DIRECTORY_SEPARATOR . uniqid());

    $this->alterRecipe($recipe_path, function (array $data) use ($config_name, $roles_permissions): array {
      $data['install'][] = 'acquia_starterkit_core';
      $data['config']['actions'][$config_name]['setThirdPartySettings'][] = [
        'module' => 'acquia_starterkit_core',
        'key' => 'roles_permissions',
        'value' => $roles_permissions,
      ];
      return $data;
    });

    $this->createRoles($roles_permissions);
    $this->applyRecipe($recipe_path);
    $this->assertRolesPermissions($roles_permissions);
  }

  /**
   * Tests permissions are granted by config import after recipe applied.
   *
   * @param string $recipe_path
   *   Given recipe path to apply.
   * @param string $config_name
   *   Given entity type configuration.
   * @param array $roles_permissions
   *   An array of roles & permissions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @dataProvider assignRolePermissionsOnRecipeApplyDataProvider
   */
  public function testGrantPermissionsByConfigImportOnRecipeApply(string $recipe_path, string $config_name, array $roles_permissions): void {
    $recipe_path = $this->cloneDirectory($recipe_path, $this->siteDirectory . DIRECTORY_SEPARATOR . uniqid());

    $this->alterRecipe($recipe_path, function (array $data): array {
      $data['install'][] = 'acquia_starterkit_core';
      return $data;
    });

    $data = Yaml::parseFile($recipe_path . "/config/$config_name.yml");
    $data['third_party_settings']['acquia_starterkit_core']['roles_permissions'] = $roles_permissions;
    file_put_contents($recipe_path . "/config/$config_name.yml", Yaml::dump($data));

    $this->createRoles($roles_permissions);
    $this->applyRecipe($recipe_path);
    $this->assertRolesPermissions($roles_permissions);
  }

  /**
   * Return an array of dataProvider for method entityInsert.
   */
  public static function assignRolePermissionsOnRecipeApplyDataProvider(): array {
    return [
      [
        DRUPAL_ROOT . "/core/recipes/article_content_type",
        'node.type.article',
        self::getRolesPermissionsByEntity("article", "content"),
      ],
      [
        DRUPAL_ROOT . "/core/recipes/image_media_type",
        "media.type.image",
        self::getRolesPermissionsByEntity("image", "media"),
      ],
    ];
  }

}
