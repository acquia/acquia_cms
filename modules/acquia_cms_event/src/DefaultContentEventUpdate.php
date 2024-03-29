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
    // Check if events that are getting imported,
    // have start date less than current date plus 2 days.
    if (strtotime($date_time['start_date']) < strtotime('+2 days')) {
      // Update new start date to current start date plus 30 days.
      $date_time['start_date'] = date('Y-m-d', strtotime('+30 days'));
      // Update new end date to new start date plus 1 day.
      if (!empty($date_time['end_date'])) {
        $date_time['end_date'] = date('Y-m-d', strtotime($date_time['start_date'] . '+1 day'));
      }
    }
    // Door time will always be same as start_date.
    $date_time['door_time'] = $date_time['start_date'];

    return $date_time;

  }

  /**
   * Update event node with modified date & time.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity objects.
   * @param array $updated_data
   *   Contains the updated event dates & time.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateEventNode(NodeInterface $entity, array $updated_data) {

    $field_event_start = new \DateTime($entity->get('field_event_start')->value);
    $field_event_end = new \DateTime($entity->get('field_event_end')->value);
    $field_door_time = new \DateTime($entity->get('field_door_time')->value);

    $entity->set('field_event_start', date('Y-m-d\T' . $field_event_start->format('H:i:s'), strtotime($updated_data['start_date'])));
    if (!empty($updated_data['end_date']) && !empty($entity->get('field_event_end')->date)) {
      $entity->set('field_event_end', date('Y-m-d\T' . $field_event_end->format('H:i:s'), strtotime($updated_data['end_date'])));

      // Updating the duration field based on start and end date of event.
      $time_diff = date_diff($field_event_end, $field_event_start);

      $day = $time_diff->d > 1 ? 'days' : 'day';
      $hour = $time_diff->h > 1 ? 'hours' : 'hour';
      $minute = $time_diff->i > 1 ? 'minutes' : 'minute';
      $entity->set(
        'field_event_duration',
        $time_diff->format("%a " . $day . ", %h " . $hour . ", %i " . $minute));
    }
    $entity->set('field_door_time', date('Y-m-d\T' . $field_door_time->format('H:i:s'), strtotime($updated_data['door_time'])));

    $entity->save();
  }

}
