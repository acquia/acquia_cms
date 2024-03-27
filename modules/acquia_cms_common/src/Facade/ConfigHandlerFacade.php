<?php

namespace Drupal\acquia_cms_common\Facade;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the configuration and settings for ACMS.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
class ConfigHandlerFacade implements ContainerInjectionInterface {

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
   * Default config settings for node revision delete.
   *
   * @var array
   */
  protected array $defaultSettings = [
    'defaults' => [
      'amount' => [
        'status' => TRUE,
        'settings' => [
          'amount' => 50,
        ],
      ],
      'created' => [
        'status' => TRUE,
        'settings' => [
          'age' => 12,
        ],
      ],
      'drafts' => [
        'status' => TRUE,
        'settings' => [
          'age' => 12,
        ],
      ],
    ],
  ];

  /**
   * Third party settings variable.
   *
   * @var array
   */
  protected array $thirdPartySettings = [
    'amount' => [
      'status' => TRUE,
      'settings' => [
        'amount' => 30,
      ],
    ],
    'created' => [
      'status' => FALSE,
      'settings' => [
        'age' => 0,
      ],
    ],
    'drafts' => [
      'status' => FALSE,
      'settings' => [
        'age' => 0,
      ],
    ],
    'drafts_only' => [
      'status' => FALSE,
      'settings' => [
        'age' => 0,
      ],
    ],
  ];

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
   * @return void
   *   Saving the default settings.
   */
  public function processConfigSettings(): void {
    $config = $this->configFactory->getEditable("$this->moduleName.settings");
    foreach ($this->defaultSettings as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
  }

  /**
   * Set third party settings for entity.
   *
   * @param string $content_type
   *   Content Type.
   *
   * @return void
   *   Saving the third party settings.
   */
  public function processThirdPartySettings(string $content_type): void {
    // Load the node type storage.
    $type = $this->entityTypeManager->getStorage('node_type')->load($content_type);
    $getThirdPartySettings = $type ? $type->get('third_party_settings') : NULL;
    if ($type && !isset($getThirdPartySettings[$this->moduleName])) {
      foreach ($this->thirdPartySettings as $key => $value) {
        $type->setThirdPartySetting($this->moduleName, $key, $value);
      }
      $type->save();
    }
  }

}
