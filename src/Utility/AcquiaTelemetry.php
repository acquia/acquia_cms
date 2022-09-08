<?php

namespace Acquia\Utility;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\State\State;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The AcquiaTelemetry class for calculating Install & Rebuild time.
 */
class AcquiaTelemetry implements ContainerInjectionInterface {

  /**
   * The date formatter service object.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $formatter;

  /**
   * The drupal state service object.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * The string constant from \DateTimeZone.
   *
   * @var string
   */
  protected $timezone;

  /**
   * The php datetime interval format.
   *
   * @var string
   */
  protected $format;

  /**
   * AcquiaTelemetry constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service object.
   * @param \Drupal\Core\State\State $state
   *   The state service object.
   * @param string $timezone
   *   The string constant from \DateTimeZone class.
   *   @link https://php.net/manual/en/class.datetimezone.php.
   * @param string $format
   *   The php datetime interval format.
   *   @link https://php.net/manual/en/dateinterval.format.php.
   */
  public function __construct(DateFormatter $date_formatter, State $state, string $timezone = "UTC", string $format = "Y-m-d h:i:s P") {
    $this->timezone = $timezone;
    $this->format = $format;
    $this->formatter = $date_formatter;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('state')
    );
  }

  /**
   * Sets the time in Drupal State for given key.
   *
   * @param string $key
   *   Key for saving data in drupal state.
   * @param string $time
   *   Time to store in drupal state. Defaults to current time.
   */
  public function setTime(string $key, string $time = 'now') {
    if (empty($this->state->get($key))) {
      $time = new DrupalDateTime($time, new \DateTimeZone($this->timezone));
      $this->state->set($key, $time->format($this->format));
    }
  }

  /**
   * Retrieves the install/rebuild time from Drupal State.
   *
   * @param string $key
   *   Key for retrieving data from drupal state.
   *
   * @return string|null
   *   Returns value from drupal state for given key.
   */
  public function getTime(string $key) {
    return $this->state->get($key);
  }

  /**
   * Removes the data from Drupal State for given keys.
   *
   * @param array $key
   *   An array of keys.
   */
  public function clearTimes(array $key) {
    $this->state->deleteMultiple($key);
  }

  /**
   * Calculates the time in seconds. Throw exception for negative time.
   *
   * @param string $start_time_key
   *   Key for retrieving data from drupal state.
   * @param string $end_time_key
   *   Key for retrieving data from drupal state.
   *
   * @return int
   *   Returns time in seconds.
   *
   * @throws \Exception
   *   Throws exception for negative time.
   */
  public function calculateTime(string $start_time_key, string $end_time_key) {
    $start_time = $this->getTime($start_time_key);
    $end_time = $this->getTime($end_time_key);
    $startTime = new DrupalDateTime($start_time);
    $endTime = new DrupalDateTime($end_time);
    if (($timeDiff = $endTime->getTimestamp() - $startTime->getTimestamp()) >= 0) {
      return $timeDiff;
    }
    throw new \Exception("Time cannot be negative");
  }

}
