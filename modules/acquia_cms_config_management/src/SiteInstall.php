<?php

namespace Drupal\acquia_cms_config_management;

/**
 * The service to determine if site is getting installed.
 */
class SiteInstall {

  /**
   * Holds the site install status.
   *
   * @var bool
   */
  public static $status;

  /**
   * Returns the site install status.
   */
  public function status(): bool {
    return self::$status ?? FALSE;
  }

  /**
   * Sets the site install status.
   */
  public function setStatus(bool $status): void {
    self::$status = $status;
  }

}
