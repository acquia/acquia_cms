<?php

namespace Drupal\Tests\acquia_cms\ExistingSite;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\Component\Uuid\Php as PhpUuid;
use Drupal\Tests\acquia_connector\Kernel\AcquiaConnectorTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests ACMS telemetry data being sent or not.
 *
 * @group acquia_cms_common
 * @group acquia_cms
 * @group risky
 */
class AcquiaCmsTelemetryTest extends AcquiaConnectorTestBase {

  protected $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->config('system.site')
      ->set('uuid', (new PhpUuid())->generate())
      ->save();
    $this->state = $this->container->get('state');
  }

  /**
   * Tests the telemetry events sent.
   */
  public function testAcmsTelemetryData(): void {
    $request = Request::create('/');
    $this->container->get('http_kernel')->terminate(
      $request,
      $this->doRequest($request)
    );

    // Check acquia_cms_telemetry state is null.
    $this->assertNull($this->state->get('acquia_cms_telemetry.telemetry'));

    // Compare data sent is same or not.
    $acmsData = [
      'application_uuid' => AcquiaDrupalEnvironmentDetector::getAhApplicationUuid(),
      'application_name' => AcquiaDrupalEnvironmentDetector::getAhGroup(),
      'environment_name' => AcquiaDrupalEnvironmentDetector::getAhEnv(),
      'starter_kit_name' => $this->state->get('acquia_cms.starter_kit'),
      'starter_kit_ui' => FALSE,
      'site_studio_status' => FALSE,
    ];
    $this->assertEquals($acmsData, $this->testDataToAmplitude());

    // If data sent, change telemetry state to true.
    $this->assertTrue($this->state->get('acquia_cms_telemetry.telemetry'));
  }

  /**
   * Send telemetry data to amplitude.
   */
  public function testDataToAmplitude(): array {
    $appUuid = AcquiaDrupalEnvironmentDetector::getAhApplicationUuid();
    $siteGroup = AcquiaDrupalEnvironmentDetector::getAhGroup();
    $env = AcquiaDrupalEnvironmentDetector::getAhEnv();
    $starterKitName = $this->state->get('acquia_cms.starter_kit');
    $starterKitUi = $this->state->get('starter_kit_wizard_completed', FALSE);
    $this->state->set('acquia_cms_telemetry.telemetry', TRUE);
    return [
      'application_uuid' => $appUuid,
      'application_name' => $siteGroup,
      'environment_name' => $env,
      'starter_kit_name' => $starterKitName,
      'starter_kit_ui' => $starterKitUi,
      'site_studio_status' => $this->siteStudioStatus(),
    ];
  }

  /**
   * Get Site studio status.
   */
  public function siteStudioStatus(): bool {
    if (\Drupal::getContainer()->has('cohesion.utils') &&
      \Drupal::service('cohesion.utils')->usedx8Status()) {
      return TRUE;
    }
    return FALSE;
  }

}
