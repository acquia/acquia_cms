<?php

namespace Drupal\Tests\acquia_cms_common\Kernel;

use Drupal\acquia_cms_common\Facade\ConfigHandlerFacade;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test the configuration and settings handling.
 *
 * @coversDefaultClass Drupal\acquia_cms_common\Facade\ConfigHandlerFacade
 */
class ConfigHandlerFacadeTest extends KernelTestBase {


  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    "node",
    "acquia_cms_common",
    "node_revision_delete",
    "reroute_email",
  ];

  /**
   * The configHandlerFacade object.
   *
   * @var \Drupal\acquia_cms_common\Facade\ConfigHandlerFacade
   */
  protected $configHandler;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->container->get("config.factory");
    $this->entityTypeManager = $this->container->get("entity_type.manager");
    $this->configHandler = new ConfigHandlerFacade(
      $this->configFactory,
      $this->entityTypeManager,
    );
  }

  /**
   * Tests config settings.
   *
   * @covers ::processConfigSettings
   * @dataProvider configSettingDataProvider
   */
  public function testConfigSettings($module, $settings): void {
    $this->configHandler->setModuleName($module);
    $this->configHandler->processConfigSettings($settings);
    $configData = $this->configFactory->getEditable("$module.settings");
    foreach ($settings as $key => $value) {
      $this->assertEquals($value, $configData->get($key));
    }
  }

  /**
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function configSettingDataProvider(): array {
    return [
      ['acquia_cms_common',
        [
          'starter_kit_name' => 'community',
        ],
      ],
      ['node_revision_delete',
        [
          'defaults' => [
            'amount' => [
              'status' => TRUE,
              'settings' => [
                'amount' => 50,
              ],
            ],
          ],
        ],
      ],
      [
        'reroute_email',
        [
          'enable' => TRUE,
          'roles' => [
            'content_editor',
          ],
        ],
      ],
    ];
  }

}
