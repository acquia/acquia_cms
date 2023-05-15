<?php

namespace Drupal\acquia_cms_image;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\Exception\DirectoryNotReadyException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create and set acquia_cms logo to the site.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class SiteLogo implements ContainerInjectionInterface {

  use StringTranslationTrait;

  const LOGO_PATH = "public://media-icons/acquia_cms_logo.png";

  /**
   * The media entity object.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * The source logo path.
   *
   * @var string
   */
  protected $logoPath;

  /**
   * The module_handler service object.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The file_system service object.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The file.repository service object.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

  /**
   * The config.factory service object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The config_installer service object.
   *
   * @var \Drupal\Core\Config\ConfigInstallerInterface
   */
  protected $configInstaller;

  /**
   * The logger service object.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs Site logo constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   An entity_type.manager service object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module_handler service object.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file_system service object.
   * @param \Drupal\file\FileRepositoryInterface $file_repository
   *   The file.repository service object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service object.
   * @param \Drupal\Core\Config\ConfigInstallerInterface $config_installer
   *   The config_installer service object.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.factory interface service object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManager $entity_type_manager, ModuleHandlerInterface $module_handler, FileSystemInterface $file_system, FileRepositoryInterface $file_repository, ConfigFactoryInterface $config_factory, ConfigInstallerInterface $config_installer, LoggerChannelInterface $logger) {
    $this->mediaStorage = $entity_type_manager->getStorage("media");
    $this->logoPath = $module_handler->getModule('acquia_cms_image')->getPath() . '/assets/images/acquia_cms_logo.png';
    $this->moduleHandler = $module_handler;
    $this->fileSystem = $file_system;
    $this->fileRepository = $file_repository;
    $this->configFactory = $config_factory;
    $this->configInstaller = $config_installer;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('file_system'),
      $container->get('file.repository'),
      $container->get('config.factory'),
      $container->get('config.installer'),
      $container->get('logger.factory')->get('acquia_cms_image')
    );
  }

  /**
   * Decides if logo media/image needs to be created.
   */
  public function validate(): bool {
    if ($this->checkIfMediaExists()) {
      $this->logger->warning('Media already exists with the uuid: 0c6f0f26-9fbb-4c2e-804c-418815aba162.');
      return FALSE;
    }
    try {
      $this->ensureDirectoryExists(dirname($this->logoPath));
    }
    catch (DirectoryNotReadyException $exception) {
      $this->logger->error($exception->getMessage());
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Checks, if media already exists.
   */
  protected function checkIfMediaExists(): bool {
    $node_loaded_by_uuid = $this->mediaStorage->loadByProperties(['uuid' => '0c6f0f26-9fbb-4c2e-804c-418815aba162']);
    return (bool) reset($node_loaded_by_uuid);
  }

  /**
   * Ensures media & dependent configurations exists.
   */
  public function ensureMediaExists(): void {
    global $install_state;
    // Import the media & related configurations, if module is being installed
    // through custom/acquia_cms profile.
    if (isset($install_state['active_task']) && $install_state['active_task'] == "install_profile_modules") {
      $optional_install_path = $this->moduleHandler->getModule('acquia_cms_image')->getPath() . "/" . InstallStorage::CONFIG_OPTIONAL_DIRECTORY;
      $storage = new FileStorage($optional_install_path, StorageInterface::DEFAULT_COLLECTION);
      $this->configInstaller->installOptionalConfig($storage, ['module' => 'media']);
      $this->logger->info('Imported media & dependent configurations, before creating media content for logo.');
    }
  }

  /**
   * Ensure if directory exists and is writable.
   *
   * @param string $directory
   *   Directory path to check.
   */
  protected function ensureDirectoryExists(string $directory): void {
    if (!is_dir($directory)) {
      if (!@mkdir($directory, 0777, TRUE)) {
        throw new DirectoryNotReadyException($directory . ' does not exist and could not be created.');
      }
    }
  }

  /**
   * Creates the logo media.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createLogo(): SiteLogo {
    if ($this->validate()) {
      $this->ensureMediaExists();
      // Upload image to public file system.
      $image_data = file_get_contents($this->logoPath);
      $image = $this->fileRepository->writeData($image_data, self::LOGO_PATH, FileSystemInterface::EXISTS_REPLACE);
      $image->setFileName('Acquia CMS logo');
      $image->setMimeType('image/png');
      $image->setPermanent();
      if ($image->save()) {
        // Create media for Acquia CMS logo.
        $this->mediaStorage->create([
          'name' => 'Acquia CMS Logo',
          'bundle' => 'image',
          'uuid' => '0c6f0f26-9fbb-4c2e-804c-418815aba162',
          'image' => [
            'target_id' => $image->id(),
            'alt' => $this->t('Acquia CMS logo'),
            'title' => $this->t('Acquia CMS logo'),
          ],
        ])->save();
      }
    }
    return $this;
  }

  /**
   * Sets the site logo.
   */
  public function setLogo(): void {
    $config = $this->configFactory->getEditable('system.theme.global');
    if (!$config->get('logo.path')) {
      $config
        ->set("logo.use_default", FALSE)
        ->set("logo.path", self::LOGO_PATH)
        ->save(TRUE);
    }
  }

}
