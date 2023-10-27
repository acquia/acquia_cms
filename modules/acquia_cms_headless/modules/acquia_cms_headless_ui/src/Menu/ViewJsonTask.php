<?php

namespace Drupal\acquia_cms_headless_ui\Menu;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the logic for "View JSON" local tasks.
 *
 * @internal
 *   This is an internal part of Acquia CMS Headless and may be changed or
 *   removed at any time without warning. External code should not extend or
 *   use this class in any way!
 */
final class ViewJsonTask extends LocalTaskDefault implements ContainerFactoryPluginInterface {

  /**
   * The JSON:API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  private $resourceTypeRepository;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ViewJsonTask constructor.
   *
   * @param array $configuration
   *   An array of plugin configuration values.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The JSON:API resource type repository.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity storage class.
   */
  public function __construct(array $configuration,
  $plugin_id,
  $plugin_definition,
  ResourceTypeRepositoryInterface $resource_type_repository,
  RouteMatchInterface $route_match,
  EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->resourceTypeRepository = $resource_type_repository;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('jsonapi.resource_type.repository'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return Cache::mergeMaxAges($this->getEntity()->getCacheMaxAge(), parent::getCacheMaxAge());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return Cache::mergeTags($this->getEntity()->getCacheTags(), parent::getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts($this->getEntity()->getCacheContexts(), parent::getCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName(): string {
    $entity = $this->getEntity();
    $resourceType = $this->resourceTypeRepository
      ->get($entity->getEntityTypeId(), $entity->bundle())
      ->getTypeName();

    return "jsonapi.$resourceType.individual";
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match): array {
    return [
      'entity' => $this->getEntity()->uuid(),
    ];
  }

  /**
   * Returns the entity being targeted by the local task.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity being targeted by the local task, based on the current route.
   */
  private function getEntity(): EntityInterface {
    // The entity_type_id option is set by
    // acquia_cms_headless_ui_local_tasks_alter().
    $entityTypeId = $this->getPluginDefinition()['entity_type_id'];

    $entity = $this->routeMatch->getParameter($entityTypeId);
    if (!$entity instanceof EntityInterface) {
      $entity = $this->entityTypeManager->getStorage($entityTypeId)->load($entity);
    }

    return $entity;
  }

}
