<?php

namespace Drupal\acquia_cms_post_config_events_test\Traits;

use Symfony\Component\Console\Exception\RuntimeException;

trait ConfigEventLogTrait {

  /**
   * An array of log types.
   *
   * @var array
   */
  public static $logTypes = ['export', 'import', 'existing_site_install'];

  /**
   * Stores the message on the state.
   *
   * @param string $logType
   *   Type of the log. Ex: export, import or existing_site_install.
   * @param string $message
   *   Message to store.
   */
  protected function log(string $logType, string $message): void {
    if (!in_array($logType, self::$logTypes)) {
      throw new RuntimeException(
        "The logType should be from one of the following:" . PHP_EOL . " - " .
        implode(PHP_EOL . " - ", self::$logTypes)
      );
    }
    $data = \Drupal::service('state')->get('invoked.post_events.' . $logType, '[]');
    $array = json_decode($data);
    $data = array_merge($array, [$message]);
    \Drupal::service('state')->set('invoked.post_events.' . $logType, json_encode($data));
  }

}
