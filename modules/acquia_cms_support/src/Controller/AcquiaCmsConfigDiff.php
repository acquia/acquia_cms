<?php

namespace Drupal\acquia_cms_support\Controller;

use Drupal\Component\Diff\Diff;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\ImportStorageTransformer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Diff\DiffFormatter;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Acquia CMS config sync route.
 */
class AcquiaCmsConfigDiff implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The target storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $targetStorage;

  /**
   * The import transformer service.
   *
   * @var \Drupal\Core\Config\ImportStorageTransformer
   */
  protected $importTransformer;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The diff formatter.
   *
   * @var \Drupal\Core\Diff\DiffFormatter
   */
  protected $diffFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage'),
      $container->get('config.manager'),
      $container->get('diff.formatter'),
      $container->get('config.import_transformer')
    );
  }

  /**
   * Constructs a ConfigDiff object.
   *
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   *   The target storage.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The config manager.
   * @param \Drupal\Core\Diff\DiffFormatter $diff_formatter
   *   The diff formatter.
   * @param \Drupal\Core\Config\ImportStorageTransformer $import_transformer
   *   The import transformer service.
   */
  public function __construct(StorageInterface $target_storage, ConfigManagerInterface $config_manager, DiffFormatter $diff_formatter, ImportStorageTransformer $import_transformer) {
    $this->targetStorage = $target_storage;
    $this->configManager = $config_manager;
    $this->diffFormatter = $diff_formatter;
    $this->importTransformer = $import_transformer;
  }

  /**
   * Shows diff of specified configuration file.
   *
   * @param string $name
   *   The name of the module.
   * @param string $type
   *   The type of the configuration file like module or profile.
   * @param string $storage
   *   The storage of the configuration file.
   * @param string $source_name
   *   The name of the configuration file.
   * @param string $target_name
   *   (optional) The name of the target configuration file if different from
   *   the $source_name.
   *
   * @return array
   *   Table showing a two-way diff between the active and staged configuration.
   *
   * @throws \Drupal\Core\Config\StorageTransformerException
   */
  public function diff($name, $type, $storage, $source_name, $target_name = NULL) {

    $module_path = \drupal_get_path($type, $name);
    $path = $module_path . '/config/' . $storage;
    $file = new FileStorage($path);
    $sync_storage = $this->importTransformer->transform($file);

    if (!isset($target_name)) {
      $target_name = $source_name;
    }
    // The output should show configuration object differences formatted as
    // YAML. But the configuration is not necessarily stored in files.
    // Therefore, they need to be read and parsed, and lastly, dumped into
    // YAML strings.
    $target_data = explode("\n", Yaml::encode($this->targetStorage->read($source_name)));
    $source_data = explode("\n", Yaml::encode($sync_storage->read($target_name)));

    $target_data = $this->removeNonRequiredKeys($target_data);

    // Check for new or removed files.
    if ($source_data === ['false']) {
      // Added file.
      // Cast the result of t() to a string, as the diff engine doesn't know
      // about objects.
      $source_data = [(string) $this->t('File added')];
    }
    if ($target_data === ['false']) {
      // Deleted file.
      // Cast the result of t() to a string, as the diff engine doesn't know
      // about objects.
      $target_data = [(string) $this->t('File removed')];
    }
    $diff = new Diff($source_data, $target_data);

    $this->diffFormatter->show_header = FALSE;
    $build = [];

    $build['#title'] = $this->t('View changes of @config_file', ['@config_file' => $source_name]);
    // Add the CSS for the inline diff.
    $build['#attached']['library'][] = 'system/diff';
    $build['#attached']['library'][] = 'acquia_cms_support/diff-modal';

    $build['diff'] = [
      '#type' => 'table',
      '#attributes' => [
        'class' => ['diff'],
      ],
      '#header' => [
        ['data' => $this->t('Staged'), 'colspan' => '2'],
        ['data' => $this->t('Active'), 'colspan' => '2'],
      ],
      '#rows' => $this->diffFormatter->format($diff),
    ];

    $build['wrapper-buttonset'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'acms-dialog-buttonset',
        ],
      ],
    ];

    $build['wrapper-buttonset']['ok'] = [
      '#type' => 'link',
      '#attributes' => [
        'class' => [
          'dialog-cancel dialog-ok-button button button--primary',
        ],
      ],
      '#title' => "OK",
      '#url' => Url::fromRoute('acquia_cms_support.config_sync'),
    ];
    return $build;
  }

  /**
   * Remove _core, uuid, default_config_hash from configurations.
   *
   * @param array $data
   *   Configuration data.
   *
   * @return array
   *   Array of configurations after removing keys.
   */
  private function removeNonRequiredKeys(array $data) {
    // Remove the _core, uuid, default_config_hash from the configuration.
    $data = array_values(array_filter(
      $data,
      function ($val) use (&$data) {
        return (strpos($val, '_core') !== 0) && (strpos(trim($val), 'default_config_hash:') !== 0) && (strpos($val, 'uuid:') !== 0);
      },
      ARRAY_FILTER_USE_BOTH
    ));
    return $data;
  }

}
