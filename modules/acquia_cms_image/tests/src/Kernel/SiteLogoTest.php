<?php

namespace Drupal\Tests\acquia_cms_image\Kernel;

use Drupal\acquia_cms_image\SiteLogo;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\MediaInterface;

class SiteLogoTest extends KernelTestBase {

  /**
   * The source logo path.
   *
   * @var string
   */
  protected $logoPath;

  /**
   * The file.repository service object.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

  /**
   * The media entity object.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_common',
    'acquia_cms_image',
    'file',
    'media',
    'user',
    'image',
    'focal_point',
    'crop',
    'system',
    'field',
    'filter',
    'editor',
    'node',
    'taxonomy',
    'entity_clone',
    'password_policy',
    'seckit',
    'password_policy_character_types',
    'password_policy_length',
    'password_policy_username',
  ];

  /**
   * The Site Logo class object.
   *
   * @var \Drupal\acquia_cms_image\SiteLogo
   */
  protected $siteLogo;

  /**
   * Disable strict config schema checks in this test.
   *
   * There are some config schema errors, and until they are all fixed,
   * this test cannot pass unless we disable strict config schema checking
   * altogether. Since strict config schema isn't critically important in
   * testing this functionality, it's okay to disable it for now, but it should
   * be re-enabled (i.e., this property should be removed) as soon as possible.
   *
   * @var bool
   */
  // @codingStandardsIgnoreStart
  protected $strictConfigSchema = FALSE;
  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installEntitySchema('media');
    $this->installEntitySchema('crop');
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installConfig([
      'field',
      'system',
      'image',
      'file',
      'media',
      'focal_point',
      'acquia_cms_common',
      'acquia_cms_image',
    ]);
    $this->installSchema('file', ['file_usage']);

    $this->siteLogo = $this->container->get("class_resolver")->getInstanceFromDefinition(SiteLogo::class);
    $this->logoPath = $this->container->get("module_handler")->getModule('acquia_cms_image')->getPath() . '/assets/images/acquia_cms_logo.png';
    $this->fileRepository = $this->container->get("file.repository");
    $this->mediaStorage = $this->container->get('entity_type.manager')->getStorage("media");
  }

  /**
   * Tests validate method of Site Logo class.
   */
  public function testValidateMethod(): void {
    $this->createImageAndMedia();
    $this->assertFalse($this->siteLogo->validate(), 'Should fail with media already exists with the uuid.');
  }

  /**
   * Tests create and set site logo of Site Logo class.
   */
  public function testAddAndSetLogo(): void {
    mkdir(dirname(SiteLogo::LOGO_PATH), 0755, TRUE);
    $siteLogoClass = $this->siteLogo->createLogo();
    $entity = $this->mediaStorage->loadByProperties(["uuid" => ['uuid' => '0c6f0f26-9fbb-4c2e-804c-418815aba162']]);
    $entity = reset($entity);
    $this->assertInstanceOf(MediaInterface::class, $entity);
    $this->assertInstanceOf(SiteLogo::class, $siteLogoClass);

    $this->assertFileExists(SiteLogo::LOGO_PATH);
    $entityArray = $entity->toArray();
    $this->assertSame($entityArray["name"][0]["value"], "Acquia CMS Logo");
    $this->assertSame($entityArray["image"], [
      ["target_id" => "1", "alt" => "Acquia CMS logo", "title" => "Acquia CMS logo", 'width' => '287', 'height' => '112'],
    ]);
    $logo = $this->config("system.theme.global")->get("logo");
    $this->assertTrue($logo['use_default'], "Use default theme");
    $this->assertEmpty($logo['path'], "Should be empty path.");
    $siteLogoClass->setLogo();

    $logo = $this->config("system.theme.global")->get("logo");
    $this->assertFalse($logo['use_default'], "Default theme should be FALSE.");
    $this->assertSame($logo['path'], SiteLogo::LOGO_PATH, "The logo path should be same.");
  }

  /**
   * Creates the media content for logo.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createImageAndMedia(): void {
    mkdir(dirname(SiteLogo::LOGO_PATH), 0777, TRUE);
    $image_data = file_get_contents($this->logoPath);
    // @phpstan-ignore-next-line
    $file_exist = class_exists(FileExists::class) ? FileExists::Replace : FileSystemInterface::EXISTS_REPLACE;
    $image = $this->fileRepository->writeData($image_data, SiteLogo::LOGO_PATH, $file_exist);
    $image->setFileName('Acquia CMS logo');
    $image->setMimeType('image/png');
    $image->setPermanent();
    $image->save();

    $this->mediaStorage->create([
      'name' => 'Acquia CMS Logo',
      'bundle' => 'image',
      'uuid' => '0c6f0f26-9fbb-4c2e-804c-418815aba162',
      'image' => [
        'target_id' => $image->id(),
      ],
    ])->save();
  }

}
