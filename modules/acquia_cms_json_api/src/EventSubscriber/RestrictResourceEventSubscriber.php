<?php

namespace Drupal\acquia_cms_json_api\EventSubscriber;

use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent;
use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber to restrict access to some resource type.
 */
class RestrictResourceEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ResourceTypeBuildEvents::BUILD => [
        ['disableResourceType'],
        ['disableResourceTypeFields'],
      ],
    ];
  }

  /**
   * Disables all resource types except 'node', 'taxonomy_term' and 'media'.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent $event
   *   The build event.
   */
  public function disableResourceType(ResourceTypeBuildEvent $event) {
    $resource_type_name = $event->getResourceTypeName();
    if (!(strpos($resource_type_name, 'taxonomy_term--') === 0 ||
        strpos($resource_type_name, 'node--') === 0 ||
        strpos($resource_type_name, 'media--') === 0)) {
      $event->disableResourceType();
    }
    foreach ($event->getFields() as $field) {
      if (in_array($field->getInternalName(), ["nid", "mid", "tid"])) {
        $event->disableField($field);
      }
    }
  }

  /**
   * Disables some resource type fields.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent $event
   *   The build event.
   */
  public function disableResourceTypeFields(ResourceTypeBuildEvent $event) {
    $resource_type_name = $event->getResourceTypeName();
    if (strpos($resource_type_name, 'taxonomy_term--') === 0 ||
        strpos($resource_type_name, 'node--') === 0 ||
        strpos($resource_type_name, 'media--') === 0) {
      foreach ($event->getFields() as $field) {
        if (in_array($field->getInternalName(), ["nid", "mid", "tid"])) {
          $event->disableField($field);
        }
      }
    }
  }

}
