<?php

namespace Drupal\Tests\acquia_cms\Kernel;

use Acquia\Utility\AcquiaTelemetry;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests AcquiaTelemetry class.
 *
 * @group acquia_cms
 * @group profile
 * @group low_risk
 * @group pr
 * @group push
 */
class AcquiaTelemetryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->telemetry = \Drupal::classResolver(AcquiaTelemetry::class);
  }

  /**
   * Time should not override when function is called multiple times.
   */
  public function testTimeNotOverride() {
    $this->telemetry->setTime('start_time', '2021-07-15 10:15:25');
    // Override the date-time for key: start_time.
    $this->telemetry->setTime('start_time', '2021-07-20 08:10:30');
    $this->assertSame("2021-07-15 10:15:25 +00:00", $this->telemetry->getTime('start_time'));

    $this->clearValues(['start_time']);
    $this->assertEmpty($this->telemetry->getTime('start_time'));
  }

  /**
   * Correct time difference should be calculated for different timezones.
   */
  public function testCalculateTimeDifferentTimeZone() {
    $this->telemetry->setTime('start_time', "2021-07-15 12:00:00 +05:30");
    $this->telemetry->setTime('end_time', "2021-07-15 08:00:00 +01:00");
    $this->assertSame(1800, $this->telemetry->calculateTime('start_time', 'end_time'));
    $this->clearValues(['start_time', 'end_time']);
  }

  /**
   * Should throw exception when time difference is negative.
   */
  public function testNegativeTimeExpectException() {
    $this->expectException(\Exception::class);
    $this->telemetry->setTime('start_time', "2021-07-15 12:00:00 +05:30");
    $this->telemetry->setTime('end_time', "2021-07-15 11:00:00 +05:00");
    $this->telemetry->calculateTime('start_time', 'end_time');
    $this->clearValues(['start_time', 'end_time']);
  }

  /**
   * Should return zero, when values doesn't exist in Drupal State.
   */
  public function testEmptyCalculateTime() {
    $this->assertSame(0, $this->telemetry->calculateTime('some_start_time', 'some_end_time'));
  }

  /**
   * Removes the data from Drupal State for given keys.
   *
   * @param array $keys
   *   An array of keys.
   */
  protected function clearValues(array $keys) {
    $this->telemetry->clearTimes($keys);
  }

}
