<?php

declare(strict_types=1);

namespace Drupal\acquia_cms_site_studio\Plugin\ConfigAction;

use Drupal\cohesion\Controller\AdministrationController;
use Drupal\cohesion_sync\Services\PackageImportHandler;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The action to import base Site Studio packages.
 */
#[ConfigAction(
  id: 'basePackageImport',
  admin_label: new TranslatableMarkup('Import base Site Studio packages.'),
)]
final class BasePackageImport implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Constructs a SimpleConfigUpdate object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Site\Settings $settings
   *    The settings.
   * @param \Drupal\cohesion_sync\Services\PackageImportHandler $packageImportHandler
   *    The package import handler.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *    The module handler.
   */
  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly Settings $settings,
    protected readonly PackageImportHandler $packageImportHandler,
    protected readonly ModuleHandler $moduleHandler,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $container->get('config.factory'),
      $container->get('settings'),
      $container->get('cohesion_sync.package_import_handler'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    if ($configName === 'cohesion.settings' && $value) {
      $this->updateConfig($configName);
    }
  }

  /**
   * Updates the configuration with API and organization keys.
   *
   * @param string $configName
   *   The name of the configuration.
   */
  private function updateConfig(string $configName): void {
    $config = $this->configFactory->get($configName);
    $apiKey = $config->get('api_key');
    $orgKey = $config->get('organization_key');

    if (!($apiKey && $orgKey)) {
      $apiKey = getenv('SITESTUDIO_API_KEY') ?? $this->settings->get('cohesion.settings')->get('api_key');
      $orgKey = getenv('SITESTUDIO_ORG_KEY') ?? $this->settings->get('cohesion.settings')->get('organization_key');
      if (!($apiKey && $orgKey)) {
        return;
      }
    }

    $this->configFactory->getEditable($configName)
      ->set('api_key', $apiKey)
      ->set('organization_key', $orgKey)
      ->save(TRUE);

    if (PHP_SAPI === 'cli' && !function_exists('drush_backend_batch_process')) {
      $this->notifyManualImport();
    } else {
      $this->importBasePackages();
    }
  }

  /**
   * Notifies the user to import packages manually from the UI.
   */
  private function notifyManualImport(): void {
    $output = new SymfonyStyle(new ArgvInput(), new ConsoleOutput());
    $output->warning($this->toPlainString(
      t('Please import site studio packages from UI: @uri.', [
        '@uri' => '/admin/cohesion/configuration/account-settings'
      ])));
  }

  /**
   * Converts a stringable like TranslatableMarkup to a plain text string.
   *
   * @param \Stringable|string $text
   *   The string to convert.
   *
   * @return string
   *   The plain text string.
   */
  private function toPlainString(\Stringable|string $text): string {
    return PlainTextOutput::renderFromHtml((string) $text);
  }

  /**
   * Imports the base Site Studio packages.
   */
  private function importBasePackages(): void {
    $this->initializeBatch();
    $package_list_path = $this->getPackageListPath();
    $this->importPackages($package_list_path);
    $this->processBatchIfCli();
  }

  /**
   * Initializes the batch process for importing packages.
   */
  private function initializeBatch(): void {
    batch_set(AdministrationController::batchAction(TRUE));
  }

  /**
   * Gets the path to the package list file.
   *
   * @return string
   *   The path to the package list file.
   */
  private function getPackageListPath(): string {
    $module_path = $this->moduleHandler->getModule('acquia_cms_site_studio')->getPath();
    return $module_path . '/config/site_studio/site_studio.packages.yml';
  }

  /**
   * Imports packages from the specified path.
   *
   * @param string $package_list_path
   *   The path to the package list file.
   */
  private function importPackages(string $package_list_path): void {
    $this->packageImportHandler->importPackagesFromPath($package_list_path);
  }

  /**
   * Processes the batch if running in CLI mode.
   */
  private function processBatchIfCli(): void {
    if (PHP_SAPI === 'cli') {
      drush_backend_batch_process();
    }
  }

}
