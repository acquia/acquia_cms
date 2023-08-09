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
      $this->container->get("http_client"),
      $this->container->get('config.factory'),
      $this->container->get("state"),
      $this->container->getParameter("site.path"),
      $this->container->get("datetime.time"),
    );
    $path = explode('/', $this->container->getParameter('site.path'));
    $this->siteUri = end($path);
  }

  /**
   * Tests the shouldSendTelemetryData() method of AcquiaCmsTelemetry class.
   *
   * @throws \ReflectionException
   */
  public function testIfTelemetryDataShouldSend(): void {
    $method = $this->getAcqauiaCmsTelemetryMethod("shouldSendTelemetryData");
    $state_service = $this->container->get("state");

    $should_send_data = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertFalse($should_send_data, "Should not send telemetry data on Non Acquia environment.");

    // This is required, otherwise test will fail on CI environment.
    putenv("CI=");

    // Fake Acquia environment and then validate.
    putenv("AH_SITE_ENVIRONMENT=dev");
    $should_send_data = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertTrue($should_send_data, "Should send telemetry data on Acquia environment.");

    putenv("CI=true");
    $should_send_data = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertFalse($should_send_data, "Should not send telemetry data on CI environment.");

    // Remove `CI` environment variable, or we can set it to false.
    putenv("CI=");

    $state_service->set("acquia_connector.telemetry.opted", FALSE);
    $should_send_data = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertFalse($should_send_data, "Should not send telemetry data if user has not opted.");

    $state_service->set("acquia_connector.telemetry.opted", TRUE);
    $state_service->set("acquia_cms_telemetry.status", TRUE);
    $should_send_data = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertFalse($should_send_data, "Should send telemetry data if user has opted & data already sent but sent data and current data is different.");

    $state_service->set("acquia_connector.telemetry.opted", TRUE);
    $state_service->set("acquia_cms_telemetry.status", FALSE);
    $should_send_data = $method->invoke($this->acquiaCmsTelemetry);
    $this->assertTrue($should_send_data, "Should send telemetry data if user has opted & data not already sent.");

    $state_service->set("acquia_connector.telemetry.opted", TRUE);
    $state_service->set("acquia_cms_telemetry.status", TRUE);
    $should_send_data = $method->invoke($this->acquiaCmsTelemetry);
    $get_data_method = $this->getAcqauiaCmsTelemetryMethod("getAcquiaCmsTelemetryData");
    $telemetry_data = $get_data_method->invoke($this->acquiaCmsTelemetry);
    $state_service->set('acquia_cms_common.telemetry_data', json_encode($telemetry_data));
    $this->assertFalse($should_send_data, "Should not send telemetry data if user has opted & data already sent and no change in data.");
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
  public function testAcquiaCmsTelemetryData(array $expected_telemetry_data, array $env_variables = []): void {
    foreach ($env_variables as $env_variable => $value) {
      putenv("$env_variable=$value");
    }
    $method = $this->getAcqauiaCmsTelemetryMethod("getExtensionInfo");
    $expected_telemetry_data['extensions'] = $method->invoke($this->acquiaCmsTelemetry);
    $expected_telemetry_data['acquia_cms']['site_uri'] = $this->siteUri;
    $expected_telemetry_data['acquia_cms']['profile'] = 'minimal';
    $method = $this->getAcqauiaCmsTelemetryMethod("getAcquiaCmsTelemetryData");
    $actual_telemetry_data = $method->invoke($this->acquiaCmsTelemetry);
    $actual_telemetry_data['acquia_cms']['site_name'] = 'Acquia CMS';
    $actual_telemetry_data['acquia_cms']['starter_kit_name'] = 'no_starter_kit';
    $actual_telemetry_data['acquia_cms']['profile'] = 'minimal';
    $this->assertSame($actual_telemetry_data, $expected_telemetry_data);
  }

  /**
   * Tests the getAcquiaCmsTelemetryData() method including Site Studio status.
   *
   * @throws \ReflectionException
   */
  public function testAcquiaCmsTelemetryDataWithSiteStudio(): void {
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
        "profile" => "",
      ],
    ];
    $method = $this->getAcqauiaCmsTelemetryMethod("getExtensionInfo");
    $expected_telemetry_data['extensions'] = $method->invoke($this->acquiaCmsTelemetry);
    $method = $this->getAcqauiaCmsTelemetryMethod("getAcquiaCmsTelemetryData");
    $actual_telemetry_data = $method->invoke($this->acquiaCmsTelemetry);
    $actual_telemetry_data['acquia_cms']['site_name'] = 'Acquia CMS';
    $actual_telemetry_data['acquia_cms']['starter_kit_name'] = 'acquia_cms_existing_site';
    $this->assertSame($actual_telemetry_data, $expected_telemetry_data);
  }

  /**
   * Tests the getAcquiaCmsTelemetryData() method including Acquia CMS version.
   *
   * @throws \ReflectionException
   */
  public function testAcquiaCmsTelemetryDataWithAcquiaCmsProfile(): void {
    $method = $this->getAcqauiaCmsTelemetryMethod("getAcquiaCmsTelemetryData");
    $actual_telemetry_data = $method->invoke($this->acquiaCmsTelemetry);
    $actual_telemetry_data['acquia_cms']['site_name'] = 'Acquia CMS Profile Site';
    $actual_telemetry_data['acquia_cms']['profile'] = 'acquia_cms';
    $actual_telemetry_data['acquia_cms']['starter_kit_name'] = 'acquia_cms_existing_site';
    $expected_telemetry_data = [
      "acquia_cms" => [
        "application_uuid" => "",
        "application_name" => "",
        "environment_name" => "",
        "acsf_status" => FALSE,
        "site_uri" => $this->siteUri,
        "site_name" => "Acquia CMS Profile Site",
        "starter_kit_name" => "acquia_cms_existing_site",
        "starter_kit_ui" => FALSE,
        "site_studio_status" => FALSE,
        "profile" => "acquia_cms",
      ],
    ];
    $method = $this->getAcqauiaCmsTelemetryMethod("getExtensionInfo");
    $expected_telemetry_data['extensions'] = $method->invoke($this->acquiaCmsTelemetry);
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
            "site_uri" => $this->siteUri,
            "site_name" => "Acquia CMS",
            "starter_kit_name" => "no_starter_kit",
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
            "acsf_status" => FALSE,
            "site_uri" => $this->siteUri,
            "site_name" => "Acquia CMS",
            "starter_kit_name" => "no_starter_kit",
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
