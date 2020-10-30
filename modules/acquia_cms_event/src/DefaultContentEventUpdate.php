<?php

namespace Drupal\acquia_cms_event;

/**
 * Update default content import data for event content type.
 */
class DefaultContentEventUpdate {

  /**
   * Update event dates.
   *
   * @param object $entity
   *   Node object.
   */
  public function updateEventDates($entity) {
    // @TODO: Make it function more dynamic, instead of passing entity pass field name and it's value.
    $event_start_date = $entity->get('field_event_start')->date->format('Y-m-d');
    if (strtotime($event_start_date) < strtotime('30 days')) {
      $new_event_start_date = date('Y-m-d\T' . $entity->get('field_event_start')->date->format('H:i:s'), strtotime('30 days'));
      $entity->set('field_event_start', $new_event_start_date);
      if (!empty($entity->get('field_event_end')->value)) {
        if ($event_start_date != $entity->get('field_event_end')->date->format('Y-m-d') && strtotime($entity->get('field_event_end')->date->format('Y-m-d')) < strtotime('30 days')) {
          $new_end_date = date('Y-m-d\T' . $entity->get('field_event_end')->date->format('H:i:s'), strtotime('30 days'));
          $entity->set('field_event_end', $new_end_date);
        }
      }
      $new_event_door_date = date('Y-m-d\T' . $entity->get('field_door_time')->date->format('H:i:s'), strtotime('30 days'));
      $entity->set('field_door_time', $new_event_door_date);
      $entity->save();
    }
  }

}
