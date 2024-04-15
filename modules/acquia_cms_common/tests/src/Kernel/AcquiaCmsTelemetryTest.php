<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_cms_common\Kernel;

use Drupal\acquia_cms_common\EventSubscriber\KernelTerminate\AcquiaCmsTelemetry;
use Drupal\Core\Extension\ModuleExtensionList;
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
  ];

  /**
   * The AcquiaCmsTelemetry event_service object.
   *
   * @var \Drupal\acquia_cms_common\EventSubscriber\KernelTerminate\AcquiaCmsTelemetry
   */
  protected $acquiaCmsTelemetry;

  /**
   * The site uri.
   *
   * @var string
   */
  protected $siteUri;

  /**
   * The module list object.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->acquiaCmsTelemetry = new AcquiaCmsTelemetry(
      $this->container->get("extension.list.module"),
      $this->container->get('config.factory'),
      $this->container->get("state"),
      $this->container->getParameter("site.path"),
      $this->container->get("datetime.time"),
      $this->container->get("logger.factory"),
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
   * Tests the shouldSendTelemetryData() method of AcquiaCmsTelemetry class.
   *
   * @throws \ReflectionException
   */
  public function testIfTelemetryDataShouldSend(): void {
    $method = $this->getAcquiaCmsTelemetryMethod("shouldSendTelemetryData");
    $state_service = $this->container->get("state");
    $datetime_service = $this->container->get('datetime.time');

    $shouldSendData = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertFalse($shouldSendData, "Should not send telemetry data on Non Acquia environment.");

    // This is required, otherwise test will fail on CI environment.
    putenv("CI=");

    // Fake Acquia environment and then validate.
    putenv("AH_SITE_ENVIRONMENT=dev");
    $shouldSendData = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertTrue($shouldSendData, "Should send telemetry data on Acquia environment.");

    $state_service->set(
      "acquia_cms_common.telemetry.timestamp",
      $datetime_service->getCurrentTime()
    );
    $shouldSendData = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertFalse($shouldSendData, "Should not send telemetry data, if data send recently.");

    $state_service->set(
      "acquia_cms_common.telemetry.timestamp",
      $datetime_service->getCurrentTime() - 86401,
    );
    $shouldSendData = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertTrue($shouldSendData, "Should send telemetry data, if data sent before a day.");

    $methodHash = $this->getAcquiaCmsTelemetryMethod("getHash");
    $state_service->set(
      "acquia_cms_common.telemetry.hash",
      $methodHash->invoke($this->acquiaCmsTelemetry),
    );
    $shouldSendData = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertFalse($shouldSendData, "Should not send telemetry data, if current telemetry data is same as data already sent.");

    $state_service->set("acquia_cms_common.telemetry.hash", 'O2X4mf9Csg8KLOIqNlUqc9dqXdsL_JE5hjKh4dRPemQ');
    $shouldSendData = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertTrue($shouldSendData, "Should send telemetry data, if current telemetry data has changed from data already sent.");

    putenv("CI=true");
    $shouldSendData = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertFalse($shouldSendData, "Should not send telemetry data on CI environment.");

    // Remove `CI` environment variable, or we can set it to false.
    putenv("CI=");

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
   * Tests the filtered module names for Acquia extensions.
   */
  public function testGetExtensionInfo() {
    $all_modules = [
      "acquia_cms_article" => [
        "name" => "Acquia CMS Article",
        "type" => "module",
        "version" => NULL,
      ],
      "acquia_cms_common" => [
        "name" => "Acquia CMS Common",
        "type" => "module",
        "version" => NULL,
      ],
      "cohesion" => [
        "name" => "Site Studio core",
        "type" => "module",
        "version" => "8.x-7.2.1",
      ],
      "metatag_mobile" => [
        "name" => "Metatag: Mobile & UI Adjustments",
        "type" => "module",
        "version" => "2.0.0",
      ],
      "metatag_hreflang" => [
        "name" => "Metatag: Hreflang",
        "type" => "module",
        "version" => "2.0.0",
      ],
    ];
    $installed_modules = [
      "acquia_cms_common" => [
        "name" => "Acquia CMS Common",
        "type" => "module",
        "version" => NULL,
      ],
      "cohesion" => [
        "name" => "Site Studio core",
        "type" => "module",
        "version" => "8.x-7.2.1",
      ],
      "metatag_mobile" => [
        "name" => "Metatag: Mobile & UI Adjustments",
        "type" => "module",
        "version" => "2.0.0",
      ],
    ];
    $module_list = $this->createMock(ModuleExtensionList::class);
    $module_list->method('getAllAvailableInfo')->willReturn($all_modules);
    $module_list->method('getAllInstalledInfo')->willReturn($installed_modules);
    $telemetry = new AcquiaCmsTelemetry(
      $module_list,
      $this->container->get('config.factory'),
      $this->container->get("state"),
      $this->container->getParameter("site.path"),
      $this->container->get("datetime.time"),
      $this->container->get("logger.factory"),
    );
    $method = $this->getAcquiaCmsTelemetryMethod("getExtensionInfo");
    $actual_data = $method->invoke($telemetry);
    $expected_data = [
      "acquia_cms_article" => [
        "version" => "dev",
        "status" => "disabled",
      ],
      "acquia_cms_common" => [
        "version" => "dev",
        "status" => "enabled",
      ],
      "cohesion" => [
        "version" => "8.x-7.2.1",
        "status" => "enabled",
      ],
    ];
    $this->assertSame($actual_data, $expected_data);
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
    $method = $this->getAcquiaCmsTelemetryMethod("getExtensionInfo");
    $expected_telemetry_data['extensions'] = $method->invoke($this->acquiaCmsTelemetry);
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
    $method = $this->getAcquiaCmsTelemetryMethod("getExtensionInfo");
    $expected_telemetry_data['extensions'] = $method->invoke($this->acquiaCmsTelemetry);
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
