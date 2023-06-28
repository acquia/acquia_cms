<?php

namespace Drupal\acquia_cms_starter\EventSubscriber;

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
  public static function getSubscribedEvents(): array {
    $events[DefaultContentEvents::IMPORT][] = ['updateEvent'];
    return $events;
  }

  /**
   * Update the event while importing the content.
   */
  public function updateEvent(ImportEvent $event) {
    $module = $event->getModule();
    if ($module === 'acquia_cms_starter') {
      foreach ($event->getImportedEntities() as $entity) {
        /** @var \Drupal\node\NodeInterface  $entity*/
        if ($entity instanceof NodeInterface && $entity->bundle() === 'event') {
          $field_event_start = new \DateTime($entity->get('field_event_start')->value);
          $field_event_end = new \DateTime($entity->get('field_event_end')->value);
          $field_door_time = new \DateTime($entity->get('field_door_time')->value);
          $date_time = [
            'start_date' => $field_event_start->format('Y-m-d'),
            'end_date' => $field_event_end->format('Y-m-d'),
            'door_time' => $field_door_time->format('Y-m-d'),
          ];
          $updated_data = $this->updateEventImport->getUpdatedDates($date_time);
          // Updating event node with modified dates.
          $this->updateEventImport->updateEventNode($entity, $updated_data);
        }
      }
    }
  }

}
