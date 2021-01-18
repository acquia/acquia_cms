<?php

namespace Drupal\acquia_cms_support\Controller;

use Drupal\acquia_cms_support\Service\AcquiaCmsConfigSyncService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Return page with list of overridden configuration related to acquia cms.
 */
class AcquiaCmsConfigSyncOverridden extends ControllerBase implements ContainerInjectionInterface {

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
      $this->t('Default parity'),
      $this->t('Operations'),
    ];
    $rows = [];
    $acquia_cms_config_lists = $this->acmsConfigSync->getAcquiaCmsConfigList();
    foreach ($acquia_cms_config_lists as $config) {
      $key = key($config);
      $delta = $config[$key]['delta'];
      $config_name = $config[$key]['name'];
      $storage = $config[$key]['storage'];
      $type = $config[$key]['type'];

      if ($delta <= 30) {
        $class_name = 'color-parity-30';
      }
      elseif ($delta > 30 && $delta <= 75) {
        $class_name = 'color-parity-75';
      }
      else {
        $class_name = 'color-parity-above-75';
      }
      if ($delta != '100') {
        $links = $this->getViewDifference($key, $type, $storage, $config_name);
        $rows[] = [
          'name' => $config_name,
          'module' => $key,
          'config' => [
            'class' => $class_name,
            'data' => ['#markup' => "<span>$delta  %</span>"],
          ],
          'operations' => [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ],
        ];
      }
    }
    asort($rows);

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attached' => [
        'library' => ['acquia_cms_support/diff-modal'],
      ],
    ];
  }

  /**
   * Get the view difference link for each configuration.
   *
   * @param string $name
   *   The configuration name.
   * @param string $type
   *   The entity type.
   * @param string $storage
   *   The storage.
   * @param string $config_file
   *   The configuration file.
   *
   * @return array
   *   The diff link in modal.
   */
  public function getViewDifference($name, $type, $storage, $config_file) {
    $links = [];
    $route_name = 'acquia_cms_support.config_diff';
    $route_options = [
      'name' => $name,
      'type' => $type,
      'storage' => $storage,
      'source_name' => $config_file,
    ];
    $links['view_diff'] = [
      'title' => $this->t('View differences'),
      'url' => Url::fromRoute($route_name, $route_options),
      'attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => json_encode([
          'width' => 700,
        ]),
      ],
    ];
    return $links;
  }

}
