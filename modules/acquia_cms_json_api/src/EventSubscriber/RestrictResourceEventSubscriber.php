<?php

namespace Drupal\acquia_cms_json_api\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent;
use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber to restrict access to some resource types.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class RestrictResourceEventSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Instantiates a RestrictResourceEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

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
    if (!$this->allowedResourceType($event->getResourceTypeName())) {
      $event->disableResourceType();
    }
  }

  /**
   * Disables some resource type fields.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent $event
   *   The build event.
   */
  public function disableResourceTypeFields(ResourceTypeBuildEvent $event) {
    if ($this->allowedResourceType($event->getResourceTypeName())) {
      $ids = [
        $this->entityTypeManager->getDefinition('node')->getKey('id'),
        $this->entityTypeManager->getDefinition('media')->getKey('id'),
        $this->entityTypeManager->getDefinition('taxonomy_term')->getKey('id'),
      ];
      foreach ($event->getFields() as $field) {
        if (in_array($field->getInternalName(), $ids)) {
          $event->disableField($field);
        }
      }
    }
  }

  /**
   * Checks if the given resource type name is from the allowed resources.
   *
   * @param string $resource_type_name
   *   The name of the resource type.
   */
  protected function allowedResourceType(string $resource_type_name) : bool {
    if (strpos($resource_type_name, 'taxonomy_term--') === 0 ||
        strpos($resource_type_name, 'node--') === 0 ||
        strpos($resource_type_name, 'media--') === 0) {
      return TRUE;
    }

    return FALSE;
  }

}
