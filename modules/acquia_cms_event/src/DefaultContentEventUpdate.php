<?php

namespace Drupal\acquia_cms_event;

/**
 * Update default content import data for event content type.
 */
class DefaultContentEventUpdate {

  /**
   * Update event dates.
   *
   * @param array $date_time
   *   Array contains event date and timing information.
   */
  public function getUpdatedDates(array $date_time) : array {
    $event_start_date = $date_time['start_date'];
    if (strtotime($event_start_date) < strtotime('+30 days') ||
      strtotime($date_time['start_date']) > strtotime($date_time['end_date'])) {

      $date_time['start_date'] = date('Y-m-d', strtotime('+30 days'));
      if (!empty($date_time['end_date'])) {
        if (strtotime($date_time['end_date']) < strtotime('+30 days')) {
          $date_time['end_date'] = date('Y-m-d', strtotime('+31 days'));
        }
      }

      if (strtotime($date_time['door_time']) > strtotime($date_time['end_date']) ||
        strtotime($date_time['door_time']) > strtotime($date_time['end_date']) ||
        strtotime($date_time['door_time']) < strtotime('+30 days')) {
        $date_time['door_time'] = date('Y-m-d', strtotime('+30 days'));
      }
    }
    return $date_time;
  }

}
