<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_cms_common\Kernel;

use Drupal\acquia_cms_common\EventSubscriber\KernelTerminate\AcquiaCmsTelemetry;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group acquia_cms_common
 */
final class AcquiaCmsTelemetryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    "system",
    "acquia_cms_common",
    "acquia_connector",
  ];

  /**
   * The AcquiaCmsTelemetry event_service object.
   *
   * @var \Drupal\acquia_cms_common\EventSubscriber\KernelTerminate\AcquiaCmsTelemetry
   */
  protected $acquiaCmsTelemetry;

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The site uri.
   *
   * @var string
   */
  protected $siteUri;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->acquiaCmsTelemetry = new AcquiaCmsTelemetry(
      $this->container->get("acquia_connector.telemetry_service"),
      $this->container->get('config.factory'),
      $this->container->get("state"),
      $this->container->getParameter("site.path"),
    );
    $path = explode('/', $this->container->getParameter('site.path'));
    $this->siteUri = end($path);
    $this->config('system.site')
      ->set('name', 'Acquia CMS')
      ->set('langcode', 'en')
      ->save();
    $this->config('acquia_cms_common.settings')
      ->set('starter_kit_name', 'no_starter_kit')->save();
    $this->config('core.extension')
      ->set('profile', 'minimal')->save();
  }

  /**
   * Tests the siteStudioStatus() method of AcquiaCmsTelemetry class.
   *
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException
   * @throws \Drupal\Core\Extension\MissingDependencyException
   * @throws \ReflectionException
   */
  public function testSiteStudioStatus(): void {
    $method = $this->getAcquiaCmsTelemetryMethod("siteStudioStatus");
    $siteStudioStatus = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertFalse($siteStudioStatus, "Should be FALSE as Site Studio is not configured.");

    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
    $moduleInstaller = $this->container->get("module_installer");
    $moduleInstaller->install(['cohesion']);
    $this->config('cohesion.settings')
      ->set("use_dx8", "enable")
      ->set("api_key", "some-random-key")
      ->save(TRUE);
    $siteStudioStatus = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertTrue($siteStudioStatus, "Should be TRUE as Site Studio is now configured.");
  }

  /**
   * Tests the getAcquiaCmsTelemetryData() method of AcquiaCmsTelemetry class.
   *
   * @throws \ReflectionException
   *
   * @dataProvider telemetryDataProvider
   */
  public function testAcquiaCmsTelemetryData(array $expected_telemetry_data, array $env_variables = []): void {
    foreach ($env_variables as $env_variable => $value) {
      putenv("$env_variable=$value");
    }
    $expected_telemetry_data['acquia_cms']['site_uri'] = $this->siteUri;
    $method = $this->getAcquiaCmsTelemetryMethod("getAcquiaCmsTelemetryData");
    $actual_telemetry_data = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertSame($actual_telemetry_data, $expected_telemetry_data);
  }

  /**
   * Tests the getAcquiaCmsTelemetryData() method including Site Studio status.
   *
   * @throws \ReflectionException
   */
  public function testAcquiaCmsTelemetryDataWithSiteStudio(): void {
    $this->config('acquia_cms_common.settings')
      ->set('starter_kit_name', 'acquia_cms_existing_site')->save();
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
    $moduleInstaller = $this->container->get("module_installer");
    $moduleInstaller->install(['cohesion']);
    $this->config('cohesion.settings')
      ->set("use_dx8", "enable")
      ->set("api_key", "some-random-key")
      ->save(TRUE);
    $expected_telemetry_data = [
      "acquia_cms" => [
        "application_uuid" => "",
        "application_name" => "",
        "environment_name" => "",
        "acsf_status" => FALSE,
        "site_uri" => $this->siteUri,
        "site_name" => "Acquia CMS",
        "starter_kit_name" => "acquia_cms_existing_site",
        "starter_kit_ui" => FALSE,
        "site_studio_status" => TRUE,
        "install_time" => "",
        "profile" => "minimal",
      ],
    ];
    $method = $this->getAcquiaCmsTelemetryMethod("getAcquiaCmsTelemetryData");
    $actual_telemetry_data = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertSame($actual_telemetry_data, $expected_telemetry_data);
  }

  /**
   * Return sample expected data to test getAcquiaCmsTelemetryData() method.
   */
  public function telemetryDataProvider(): array {
    return [
      [
        [
          "acquia_cms" => [
            "application_uuid" => "",
            "application_name" => "",
            "environment_name" => "",
            "acsf_status" => FALSE,
            "site_uri" => "",
            "site_name" => "Acquia CMS",
            "starter_kit_name" => "no_starter_kit",
            "starter_kit_ui" => FALSE,
            "site_studio_status" => FALSE,
            "install_time" => "",
            "profile" => "minimal",
          ],
        ],
      ],
      [
        [
          "acquia_cms" => [
            "application_uuid" => "some-application-uuid",
            "application_name" => "some-application-name",
            "environment_name" => "some-environment-name",
            "acsf_status" => FALSE,
            "site_uri" => "",
            "site_name" => "Acquia CMS",
            "starter_kit_name" => "no_starter_kit",
            "starter_kit_ui" => FALSE,
            "site_studio_status" => FALSE,
            "install_time" => "",
            "profile" => "minimal",
          ],
        ],
        [
          "AH_APPLICATION_UUID" => "some-application-uuid",
          "AH_SITE_GROUP" => "some-application-name",
          "AH_SITE_ENVIRONMENT" => "some-environment-name",
        ],
      ],
    ];
  }

  /**
   * Returns the AcquiaCmsTelemetry ReflectionMethod object.
   *
   * @throws \ReflectionException
   */
  protected function getAcquiaCmsTelemetryMethod(string $method_name): \ReflectionMethod {
    $class = new \ReflectionClass($this->acquiaCmsTelemetry);
    $method = $class->getMethod($method_name);
    $method->setAccessible(TRUE);
    return $method;
  }

}
