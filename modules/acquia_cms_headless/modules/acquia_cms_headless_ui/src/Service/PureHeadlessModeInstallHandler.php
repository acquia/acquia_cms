<?php

namespace Drupal\acquia_cms_headless_ui\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\path_alias\Entity\PathAlias;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Install handlers for Headless Next.js Starterkit.
 *
 * Provides a series of helper functions for setting up the Next.js Starterkit
 * and the various entity types used by it.
 */
class PureHeadlessModeInstallHandler {

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Simple OAUTH Key Generator service.
   *
   * @var \Drupal\simple_oauth\Service\KeyGeneratorService
   */
  protected $keyGeneratorService;

  /**
   * Include the messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;


  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;


  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(AliasManagerInterface $alias_manager, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    $this->aliasManager = $alias_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path_alias.manager'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('module_handler'),
      $container->get('theme_handler')
    );
  }

  /**
   * Establishes a set of path aliases.
   *
   * @return array
   *   Returns an array of path aliases.
   */
  public function headlessAliases(): array {
    return [
      '/admin/config/people/simple_oauth' => '/admin/access/settings',
      '/admin/config/people/simple_oauth/oauth2_client' => '/admin/access/clients',
      '/admin/people/roles' => '/admin/access/roles',
      '/admin/config/people/simple_oauth/oauth2_token' => '/admin/access/tokens',
      '/admin/people' => '/admin/access/users',
      '/admin/structure/types' => '/admin/content-models/content',
      '/admin/structure/media' => '/admin/content-models/media',
      '/admin/structure/taxonomy' => '/admin/content-models/categories',
      '/admin/config/people/accounts' => '/admin/content-models/users',
      '/admin/structure/block/block-content' => '/admin/content/blocks',
      '/admin/structure/block-content' => '/admin/content-models/blocks',
    ];
  }

  /**
   * Creates a set of new path aliases for the headless ui experience.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createHeadlessUiPaths() {
    // Get the list of aliases.
    $headlessAliases = $this->headlessAliases();

    // Iterate through each item so that we can set a new path alias.
    foreach ($headlessAliases as $path => $alias) {
      // Get a list of aliases by passing in our path.
      $getExistingAliases = $this->aliasManager->getAliasByPath($path);

      // If the alias does not exist, then create it.
      if ($alias != $getExistingAliases) {
        PathAlias::create([
          'path' => $path,
          'alias' => $alias,
        ])->save();
      }
    }

    // Clear alias cache.
    $this->aliasManager->cacheClear();
  }

  /**
   * Remove any Aliases related to the Headless UI.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteHeadlessUiPaths() {
    // Get the list of aliases.
    $headlessAliases = $this->headlessAliases();
    $aliasStorage = $this->entityTypeManager->getStorage('path_alias');

    // Iterate through each item so that we can appropriately target which
    // aliases to remove on uninstall.
    foreach ($headlessAliases as $path => $alias) {
      // Get a list of aliases by passing in our path.
      $getExistingAliases = $this->aliasManager->getAliasByPath($path);

      // Load our aliases by the path/alias properties.
      $aliasObjects = $aliasStorage->loadByProperties(['alias' => $alias]);
      if ($alias == $getExistingAliases) {
        foreach ($aliasObjects as $aliasObject) {
          $aliasObject->delete();
        }
      }
    }

    // Clear alias cache.
    $this->aliasManager->cacheClear();
  }

  /**
   * Update Headless UI config.
   *
   * @param bool $isEnabled
   *   Accepts a TRUE/FALSE boolean value to determine which config updates
   *   to run.
   */
  public function updateHeadlessUiConfig(bool $isEnabled = TRUE) {
    if ($isEnabled) {
      // Reset default theme to acquia_claro.
      if ($this->themeHandler->themeExists('acquia_claro')) {
        $this->configFactory
          ->getEditable('system.theme')
          ->set('default', 'acquia_claro')
          ->save();
      }

      // Update 403 and Frontpage values in Site Settings.
      $this->configFactory
        ->getEditable('system.site')
        ->set('page.403', '/user/login')
        ->set('page.front', '/frontpage')
        ->save();

      // Turn off moderation dashboard redirect as it interferes with our on
      // custom login redirect handling.
      if ($this->moduleHandler->moduleExists('moderation_dashboard')) {
        $this->configFactory
          ->getEditable('moderation_dashboard.settings')
          ->set('redirect_on_login', FALSE)
          ->save();
      }

      // Remove link to entity setting from Content list view in order to
      // disable access to node/[node_id] type routes.
      $this->configFactory
        ->getEditable('views.view.content')
        ->set('display.default.display_options.fields.title.settings.link_to_entity', FALSE)
        ->save();

      $this->configFactory
        ->getEditable('acquia_cms_headless.settings')
        ->set('headless_mode', TRUE)
        ->save();

    }
    else {
      // Update 403 and Frontpage values in Site Settings.
      $this->configFactory
        ->getEditable('system.site')
        ->set('page.403', '')
        ->save();

      // Restore moderation dashboard redirect settings back to its default
      // state.
      if ($this->moduleHandler->moduleExists('moderation_dashboard')) {
        $this->configFactory
          ->getEditable('moderation_dashboard.settings')
          ->set('redirect_on_login', TRUE)
          ->save();
      }

      // Restore link to entity setting from Content list view in order to allow
      // access to node/[node_id] type routes.
      $this->configFactory
        ->getEditable('views.view.content')
        ->set('display.default.display_options.fields.title.settings.link_to_entity', TRUE)
        ->save();

      $this->configFactory
        ->getEditable('acquia_cms_headless.settings')
        ->set('headless_mode', FALSE)
        ->save();
    }
  }

}
