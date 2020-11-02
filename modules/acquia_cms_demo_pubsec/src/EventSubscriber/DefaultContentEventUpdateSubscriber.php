<?php

namespace Drupal\acquia_cms_demo_pubsec\EventSubscriber;

use Drupal\acquia_cms_event\DefaultContentEventUpdate;
use Drupal\default_content\Event\DefaultContentEvents;
use Drupal\default_content\Event\ImportEvent;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber DefaultEventContentSubscriber.
 */
class DefaultContentEventUpdateSubscriber implements EventSubscriberInterface {

  /**
   * Default content event update.
   *
   * @var \Drupal\acquia_cms_event\DefaultContentEventUpdate
   */
  protected $updateEventImport;

  /**
   * Constuctor.
   *
   * @param \Drupal\acquia_cms_event\DefaultContentEventUpdate $update_event_import
   *   The config factory.
   */
  public function __construct(DefaultContentEventUpdate $update_event_import) {
    $this->updateEventImport = $update_event_import;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[DefaultContentEvents::IMPORT][] = ['updateEvent'];
    return $events;
  }

  /**
   * Update the event while importing the content.
   */
  public function updateEvent(ImportEvent $event) {
    $module = $event->getModule();
    if ($module == 'acquia_cms_demo_pubsec') {
      foreach ($event->getImportedEntities() as $entity) {
        /** @var \Drupal\node\NodeInterface */
        if ($entity instanceof NodeInterface && $entity->bundle() === 'event') {
          $date_time = [
            'start_date' => $entity->get('field_event_start')->date->format('Y-m-d'),
            'end_date' => !empty($entity->get('field_event_end')->value) ? $entity->get('field_event_end')->date->format('Y-m-d') : '',
            'door_time' => $entity->get('field_door_time')->date->format('Y-m-d'),
          ];
          $updated_data = $this->updateEventImport->getUpdatedDates($date_time);
          // Updating event node with modified dates.
          $this->updateEventNode($entity, $updated_data);
        }
      }
    }
  }

  /**
   * Update event node with modified date & time.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity object.
   * @param array $updated_data
   *   Contains the updated event dates & time.
   */
  private function updateEventNode(NodeInterface $entity, array $updated_data) {
    $entity->set('field_event_start', date('Y-m-d\T' . $entity->get('field_event_start')->date->format('H:i:s'), strtotime($updated_data['start_date'])));
    if (!empty($date_time['end_date'])) {
      $entity->set('field_event_end', date('Y-m-d\T' . $entity->get('field_event_end')->date->format('H:i:s'), strtotime($updated_data['end_date'])));
    }
    $entity->set('field_door_time', date('Y-m-d\T' . $entity->get('field_door_time')->date->format('H:i:s'), strtotime($updated_data['door_time'])));

    $entity->save();
  }

}
