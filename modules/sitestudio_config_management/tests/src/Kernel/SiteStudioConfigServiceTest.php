<?php

namespace Drupal\Tests\sitestudio_config_management\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests site studio config service.
 *
 * @group sitestudio_config_management
 * @group acquia_cms
 */
class SiteStudioConfigServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    "sitestudio_config_management",
    "cohesion",
    "file",
  ];

  /**
   * The state service object.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The module_installer service object.
   *
   * @var \Drupal\Core\Extension\ModuleInstaller
   */
  protected $moduleInstaller;

  /**
   * The module_handler service object.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The site_studio.config_management service object.
   *
   * @var \Drupal\sitestudio_config_management\SiteStudioConfigManagement
   */
  protected $siteStudioConfigService;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->state = $this->container->get("state");
    $this->moduleInstaller = $this->container->get('module_installer');
    $this->moduleExtensionList = $this->container->get('extension.list.module');
    $this->siteStudioConfigService = $this->container->get("site_studio.config_management");
    $this->installConfig(["cohesion"]);
    $this->installSchema('cohesion', ['coh_usage']);
    $this->siteStudioConfigService->initialize();
  }

  /**
   * Performs tests to retrieve & set Site Studio version from Drupal state.
   *
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException
   * @throws \Drupal\Core\Extension\MissingDependencyException
   */
  public function testSiteStudioVersionFromState(): void {
    $extension = $this->moduleExtensionList->get("cohesion")->info;
    $this->assertSame($extension['version'], $this->state->get("sitestudio_config_management.site_studio_version"));
    $this->moduleInstaller->uninstall(['sitestudio_config_management']);
    $this->state->resetCache();
    $this->assertNull($this->state->get("sitestudio_config_management.site_studio_version"));
  }

  /**
   * Tests the current/previous Site Studio version.
   */
  public function testSiteStudioVersions(): void {
    $this->assertNotEmpty($this->siteStudioConfigService->getCurrentVersion());
    $this->assertNotEmpty($this->siteStudioConfigService->getPreviousVersion());
    $this->assertSame($this->siteStudioConfigService->getCurrentVersion(), $this->siteStudioConfigService->getPreviousVersion());
  }

  /**
   * Tests to validate if Site Studio is upgraded.
   *
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException
   * @throws \Drupal\Core\Extension\MissingDependencyException
   */
  public function testSiteStudioUpgraded(): void {
    $this->assertFalse($this->siteStudioConfigService->isSiteStudioUpgraded());
    $this->state->set("sitestudio_config_management.site_studio_version", "8.x-6.8.2");
    $this->assertTrue($this->siteStudioConfigService->isSiteStudioUpgraded());
  }

  /**
   * Tests to validate if Site Studio is configured.
   */
  public function testSiteStudioConfigured(): void {
    $this->assertFalse($this->siteStudioConfigService->isSiteStudioConfigured());
    $this->config("cohesion.settings")
      ->set("api_key", "some-api-url")
      ->set("organization_key", "some-organization-key")->save(TRUE);
    $this->assertTrue($this->siteStudioConfigService->isSiteStudioConfigured());
  }

}
