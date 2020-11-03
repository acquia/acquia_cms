<?php

namespace Drupal\acquia_cms_event;

use Drupal\node\NodeInterface;

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
    if (strtotime($date_time['start_date']) < strtotime('+30 days') ||
      strtotime($date_time['start_date']) > strtotime($date_time['end_date'])) {
      $date_time['start_date'] = date('Y-m-d', strtotime('+30 days'));
    }

    if (!empty($date_time['end_date'])) {
      if (strtotime($date_time['end_date']) < strtotime('+30 days') ||
        strtotime($date_time['end_date']) < strtotime($date_time['start_date'])) {
        $date_time['end_date'] = date('Y-m-d', strtotime('+31 days'));
      }
    }

    if (strtotime($date_time['door_time']) > strtotime($date_time['end_date']) ||
      strtotime($date_time['door_time']) < strtotime('+30 days')) {
      $date_time['door_time'] = date('Y-m-d', strtotime('+30 days'));
    }
    return $date_time;

  }

  /**
   * Update event node with modified date & time.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity object.
   * @param array $updated_data
   *   Contains the updated event dates & time.
   */
  public function updateEventNode(NodeInterface $entity, array $updated_data) {
    $entity->set('field_event_start', date('Y-m-d\T' . $entity->get('field_event_start')->date->format('H:i:s'), strtotime($updated_data['start_date'])));
    if (!empty($updated_data['end_date'])) {
      $entity->set('field_event_end', date('Y-m-d\T' . $entity->get('field_event_end')->date->format('H:i:s'), strtotime($updated_data['end_date'])));
    }
    $entity->set('field_door_time', date('Y-m-d\T' . $entity->get('field_door_time')->date->format('H:i:s'), strtotime($updated_data['door_time'])));

    $entity->save();
  }

}
