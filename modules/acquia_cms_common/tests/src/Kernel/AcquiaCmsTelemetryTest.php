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
   * The AcquiaCmsTelemetry event_service object.
   *
   * @var \Drupal\acquia_cms_common\EventSubscriber\KernelTerminate\AcquiaCmsTelemetry
   */
  protected $acquiaCmsTelemetry;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->acquiaCmsTelemetry = new AcquiaCmsTelemetry(
      $this->container->get("extension.list.module"),
      $this->container->get("http_client"),
      $this->container->get("config.factory"),
      $this->container->get("state")
    );
  }

  /**
   * Tests the shouldSendTelemetryData() method of AcquiaCmsTelemetry class.
   *
   * @throws \ReflectionException
   */
  public function testIfTelemetryDataShouldSend(): void {
    $method = $this->getAcqauiaCmsTelemetryMethod("shouldSendTelemetryData");
    $state_service = $this->container->get("state");

    $shouldSendData = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertFalse($shouldSendData, "Should not send telemetry data on Non Acquia environment.");

    // This is required, otherwise test will fail on CI environment.
    putenv("CI=");

    // Fake Acquia environment and then validate.
    putenv("AH_SITE_ENVIRONMENT=dev");
    $shouldSendData = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertTrue($shouldSendData, "Should send telemetry data on Acquia environment.");

    putenv("CI=true");
    $shouldSendData = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertFalse($shouldSendData, "Should not send telemetry data on CI environment.");

    // Remove `CI` environment variable, or we can set it to false.
    putenv("CI=");

    $state_service->set("acquia_connector.telemetry.opted", FALSE);
    $shouldSendData = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertFalse($shouldSendData, "Should not send telemetry data if user has not opted.");

    $state_service->set("acquia_connector.telemetry.opted", TRUE);
    $state_service->set("acquia_cms_telemetry.status", TRUE);
    $shouldSendData = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertFalse($shouldSendData, "Should not send telemetry data if user has opted & data already sent.");

    $state_service->set("acquia_connector.telemetry.opted", TRUE);
    $state_service->set("acquia_cms_telemetry.status", FALSE);
    $shouldSendData = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertTrue($shouldSendData, "Should send telemetry data if user has opted & data not already sent.");
  }

  /**
   * Tests the siteStudioStatus() method of AcquiaCmsTelemetry class.
   *
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException
   * @throws \Drupal\Core\Extension\MissingDependencyException
   * @throws \ReflectionException
   */
  public function testSiteStudioStatus(): void {
    $method = $this->getAcqauiaCmsTelemetryMethod("siteStudioStatus");
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
  public function testAcquiaCmsTelemetryData(array $expected_telemetryData, array $env_variables = []): void {
    $method = $this->getAcqauiaCmsTelemetryMethod("getAcquiaCmsTelemetryData");
    foreach ($env_variables as $env_variable => $value) {
      putenv("$env_variable=$value");
    }
    $actual_telemetryData = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertSame($actual_telemetryData, $expected_telemetryData);
  }

  /**
   * Tests the getAcquiaCmsTelemetryData() method including Site Studio status.
   *
   * @throws \ReflectionException
   */
  public function testAcquiaCmsTelemetryDataWithSiteStudio(): void {
    $method = $this->getAcqauiaCmsTelemetryMethod("getAcquiaCmsTelemetryData");

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
        "starter_kit_name" => "existing_site_acquia_cms",
        "starter_kit_ui" => FALSE,
        "site_studio_status" => TRUE,
      ],
    ];
    $actual_telemetryData = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertSame($actual_telemetryData, $expected_telemetry_data);
  }

  /**
   * Tests the getAcquiaCmsTelemetryData() method including Acquia CMS version.
   *
   * @throws \ReflectionException
   */
  public function testAcquiaCmsTelemetryDataWithAcquiaCmsVersion(): void {
    $module_list = $this->createMock(ModuleExtensionList::class);
    $module_list->method('getAllInstalledInfo')->willReturn([
      "acquia_cms" => [
        "version" => "1.5.2",
      ],
    ]);

    $acquia_cms_telemetry = new AcquiaCmsTelemetry(
      $module_list,
      $this->container->get("http_client"),
      $this->container->get("config.factory"),
      $this->container->get("state"),
    );
    $class = new \ReflectionClass($acquia_cms_telemetry);
    $method = $class->getMethod("getAcquiaCmsTelemetryData");
    $method->setAccessible(TRUE);
    $actual_telemetryData = $method->invoke($acquia_cms_telemetry);
    $expected_telemetry_data = [
      "acquia_cms" => [
        "application_uuid" => "",
        "application_name" => "",
        "environment_name" => "",
        "starter_kit_name" => "existing_site_acquia_cms",
        "starter_kit_ui" => FALSE,
        "site_studio_status" => FALSE,
        "version" => "1.5.2",
      ],
    ];
    $this->assertSame($actual_telemetryData, $expected_telemetry_data);
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
            "starter_kit_name" => "existing_site_acquia_cms",
            "starter_kit_ui" => FALSE,
            "site_studio_status" => FALSE,
          ],
        ],
      ],
      [
        [
          "acquia_cms" => [
            "application_uuid" => "some-application-uuid",
            "application_name" => "some-application-name",
            "environment_name" => "some-environment-name",
            "starter_kit_name" => "existing_site_acquia_cms",
            "starter_kit_ui" => FALSE,
            "site_studio_status" => FALSE,
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
  protected function getAcqauiaCmsTelemetryMethod(string $method_name): \ReflectionMethod {
    $class = new \ReflectionClass($this->acquiaCmsTelemetry);
    $method = $class->getMethod($method_name);
    $method->setAccessible(TRUE);
    return $method;
  }

}
