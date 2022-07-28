<?php

namespace Drupal\acquia_cms_support\Controller;

use Drupal\acquia_cms_common\Services\AcmsUtilityService;
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
   * The acquia cms utility service.
   *
   * @var \Drupal\acquia_cms_common\Services\AcmsUtilityService
   */
  protected $acmsUtilityService;

  /**
   * AcquiaCmsConfigSyncOverridden constructor.
   *
   * @param \Drupal\acquia_cms_support\Service\AcquiaCmsConfigSyncService $acms_config_sync
   *   The acquia cms config sync.
   * @param \Drupal\acquia_cms_common\Services\AcmsUtilityService $acmsUtilityService
   *   The acquia cms utility service.
   */
  public function __construct(AcquiaCmsConfigSyncService $acms_config_sync, AcmsUtilityService $acmsUtilityService) {
    $this->acmsConfigSync = $acms_config_sync;
    $this->acmsUtilityService = $acmsUtilityService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('acquia_cms_support.config_service'),
      $container->get('acquia_cms_common.utility')
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

    $acquiaCmsModules = $this->acmsUtilityService->getAcquiaCmsProfileModuleList();
    $unChangedConfigList = [];

    foreach ($acquiaCmsModules as $module) {
      $path = $module->getPath();
      $multipleStorage = [
        'install' => $this->acmsConfigSync->getInstallStorage($path),
        'optional' => $this->acmsConfigSync->getOptionalStorage($path),
      ];
      foreach ($multipleStorage as $storage) {
        $unChangedList = $this->acmsConfigSync->getUnChangedConfig($storage);
        if (empty($unChangedList)) {
          continue;
        }
        foreach ($unChangedList as $config) {
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
