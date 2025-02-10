<?php

declare(strict_types=1);

namespace Drupal\acquia_starterkit_core\Plugin\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The action to update fields bundle settings.
 */
#[ConfigAction(
  id: 'addBundlesToField',
  admin_label: new TranslatableMarkup('Add bundle to reference field'),
)]
final class AddBundlesToField implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Constructs a SimpleConfigUpdate object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   The config manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The configuration manager.
   */
  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly ConfigManagerInterface $configManager,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $values): void {
    if (!is_array($values)) {
      $values = [$values];
    }

    $field = $this->configManager->loadConfigEntityByName($configName);
    assert($field instanceof FieldConfigInterface);
    $settings = $field->get('settings');

    $handler = $settings['handler'] ?? '';
    // Check if the handler is defined and starts with 'default:'.
    if (!$handler) {
      throw new ConfigActionException("The 'handler' setting is not defined in the entity configuration.");
    }

    if (!str_starts_with($handler, "default:")) {
      throw new ConfigActionException("Unsupported handler: '{$handler}'. The handler must start with 'default:'.");
    }

    // Determine the target entity type from the handler.
    $target_entity = str_replace('default:', '', $handler);
    foreach ($values as $value) {
      $entity_type_id = $this->entityTypeManager->getDefinition($target_entity)->getBundleEntityType();
      // Throw an exception if the bundle does not exist.
      if (!$this->entityTypeManager->getStorage($entity_type_id)->load($value)) {
        throw new ConfigActionException(sprintf('The bundle %s does not exist, so it can not be added.', $value));
      }
    }

    $settings = $field->get('settings');
    $settings['handler_settings']['target_bundles'] = array_merge($settings['handler_settings']['target_bundles'], $values);
    $field->setSettings($settings)->save();
  }

}
