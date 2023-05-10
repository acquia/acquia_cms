<?php

namespace Acquia\Drupal\RecommendedSettings\Exceptions;

/**
 * Custom reporting and error handling for exceptions.
 *
 * @package Acquia\Blt\Robo\Exceptions
 */
class SettingsException extends \Exception {

  /**
   * Report exception.
   */
  public function __construct(
        $message = "",
        $code = 0,
        \Throwable $previous = NULL
    ) {

    $message .= "\nFor troubleshooting guidance and support, see https://docs.acquia.com/blt/support/";
    parent::__construct($message, $code, $previous);

    $this->transmitAnalytics();
  }

  /**
   * Transmit anonymous data about Exception.
   */
  protected function transmitAnalytics() {
    // Create new BltAnalyticsData class.
  }

}
