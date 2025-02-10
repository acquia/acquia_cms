<?php

namespace Drupal\acquia_starterkit_core\EntityOperations;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to field config entity events.
 *
 * @internal
 */
class FieldBundleUpdater implements ContainerInjectionInterface {

  /**
   * Constructs the FieldBundleUpdater object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The configuration manager.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function entityPresave(ConfigEntityInterface $entity): void {
    if ($this->shouldSkipOperations($entity)) {
      return;
    }
    $target_bundles = $this->getBundlesToAdd($entity);
    $target_bundles = $this->filterBundles($entity, $target_bundles);
    if ($target_bundles) {
      $this->addBundlesToField($entity, $target_bundles);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function shouldSkipOperations(ConfigEntityInterface $entity): bool {
    if (!$this->getBundlesToAdd($entity) || $entity->isSyncing()) {
      return TRUE;
    }
    if (!$entity->isNew() && $entity->get('original') instanceof ConfigEntityInterface) {
      $before_target_bundles = $this->getBundlesToAdd($entity->get('original'));
      $current_target_bundles = $this->getBundlesToAdd($entity);
      if ($before_target_bundles === $current_target_bundles) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Filters the target bundles.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The given config object.
   * @param array $target_bundles
   *   An array of target_bundles.
   *
   * @throws \Exception
   */
  protected function filterBundles(ConfigEntityInterface $entity, array $target_bundles): array {
    $handler = $entity->get('settings')['handler'] ?? '';

    // Check if the handler is defined and starts with 'default:'.
    if (!$handler) {
      throw new \Exception("The 'handler' setting is not defined in the entity configuration.");
    }

    if (!str_starts_with($handler, "default:")) {
      throw new \Exception("Unsupported handler: '{$handler}'. The handler must start with 'default:'.");
    }

    // Determine the target entity type from the handler.
    $target_entity = str_replace('default:', '', $handler);

    // Filter each target bundle.
    return array_filter($target_bundles, function ($target_bundle) use ($target_entity) {
      $entity_type_id = $this->entityTypeManager->getDefinition($target_entity)->getBundleEntityType();
      return $this->entityTypeManager->getStorage($entity_type_id)->load($target_bundle);
    });
  }

  /**
   * Adds the target bundles to the field.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The given config object.
   * @param array $target_bundles
   *   An array of target_bundles.
   */
  protected function addBundlesToField(ConfigEntityInterface $entity, array $target_bundles): void {
    $settings = $entity->get('settings');
    $settings['handler_settings']['target_bundles'] = array_merge($settings['handler_settings']['target_bundles'], $target_bundles);
    $entity->set('settings', $settings);
  }

  /**
   * Returns the list of bundles to add.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The given entity object.
   */
  protected function getBundlesToAdd(ConfigEntityInterface $entity): array {
    return $entity->getThirdPartySettings('acquia_starterkit_core')['target_bundles']['add'] ?? [];
  }

}
