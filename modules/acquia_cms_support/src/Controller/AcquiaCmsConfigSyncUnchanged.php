<?php

namespace Drupal\acquia_cms_support\Controller;

use Drupal\acquia_cms_support\Service\AcquiaCmsConfigSyncService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a route controller providing a Config sync for acquia cms.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
class AcquiaCmsConfigSyncUnchanged extends ControllerBase implements ContainerInjectionInterface {

  /**
   * AcquiaCmsConfigSyncService.
   *
   * @var \Drupal\acquia_cms_support\Service\AcquiaCmsConfigSyncService
   */
  protected $acmsConfigSync;

  /**
   * AcquiaCmsConfigSyncOverridden constructor.
   *
   * @param \Drupal\acquia_cms_support\Service\AcquiaCmsConfigSyncService $acms_config_sync
   *   The acquia cms config sync.
   */
  public function __construct(AcquiaCmsConfigSyncService $acms_config_sync) {
    $this->acmsConfigSync = $acms_config_sync;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('acquia_cms_support.config_service')
    );
  }

  /**
   * Returns a renderable array for a configuration page.
   *
   * @throws \Drupal\Core\Config\StorageTransformerException
   */
  public function build() {
    $header = [
      $this->t('Name'),
      $this->t('Module'),
    ];

    $acquiaCmsModules = $this->acmsConfigSync->getAcquiaCmsProfileModuleList();
    $unChangedConfigList = [];

    foreach ($acquiaCmsModules as $module) {
      $path = $module->getPath();
      $multipleStorage = [
        'install' => $this->acmsConfigSync->getInstallStorage($path),
        'optional' => $this->acmsConfigSync->getOptionalStorage($path),
      ];
      foreach ($multipleStorage as $storage) {
        $configChangeList = $this->acmsConfigSync->getOverriddenConfig($storage);
        if (empty($configChangeList)) {
          continue;
        }
        foreach ($configChangeList as $config) {
          $delta = (int) $this->acmsConfigSync->getDelta($config, $storage);
          if ($delta !== 100) {
            continue;
          }

          $unChangedConfigList[] = [
            'name' => $config,
            'module' => $module->getName(),
          ];
        }
      }
    }

    asort($unChangedConfigList);

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $unChangedConfigList,
      '#attached' => [
        'library' => ['acquia_cms_support/diff-modal'],
      ],
    ];
  }

}
