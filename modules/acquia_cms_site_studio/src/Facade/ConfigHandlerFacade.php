<?php

namespace Drupal\acquia_cms_site_studio\Facade;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the configuration and settings for Acquia Starter Kit Site Studio.
 *
 * @internal
 *   This is a totally internal part of Acquia Starter Kits and may be changed
 *   in any way, or removed outright, at any time without warning. External
 *   code should not use this class!
 */
final class ConfigHandlerFacade implements ContainerInjectionInterface {

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
   * The Module name.
   *
   * @var string
   */
  protected string $moduleName;

  /**
   * ConfigHandlerFacade constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Used to obtain data from various entity types.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Set module name.
   *
   * @param string $name
   *   Module name.
   *
   * @return void
   *   Setting the module name.
   */
  public function setModuleName(string $name): void {
    $this->moduleName = $name;
  }

  /**
   * Function to add default settings of node revision delete.
   *
   * @param array $settings
   *   Config settings data.
   *
   * @return void
   *   Saving the default settings.
   */
  public function processConfigSettings(array $settings): void {
    $config = $this->configFactory->getEditable("$this->moduleName.settings");
    foreach ($settings as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
  }

  /**
   * Set third party settings for entity.
   *
   * @param array $entity
   *   Entity data.
   * @param array $settings
   *   Third party setting data.
   *
   * @return void
   *   Saving the third party settings.
   */
  public function processThirdPartySettings(array $entity, array $settings): void {
    if ($entity) {
      // Get the entity storage with respective entity_type.
      $type = $this->entityTypeManager->getStorage($entity['entity_type']);
      $entityLoad = $type->load($entity['bundle']);
      // @phpstan-ignore-next-line
      $getThirdPartySettings = $entityLoad ? $entityLoad->get('third_party_settings') : NULL;
      // Set the third party settings.
      if (!empty($entityLoad) && !isset($getThirdPartySettings[$this->moduleName])) {
        foreach ($settings as $key => $value) {
          // @phpstan-ignore-next-line
          $entityLoad->setThirdPartySetting($this->moduleName, $key, $value);
        }
        $entityLoad->save();
      }
    }
  }

}
