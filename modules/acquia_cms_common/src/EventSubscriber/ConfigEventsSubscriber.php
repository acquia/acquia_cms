<?php

namespace Drupal\acquia_cms_common\EventSubscriber;

use Drupal\acquia_cms_search\Facade\SearchFacade;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EntityTypeSubscriber.
 *
 * @package Drupal\acquia_cms_common\EventSubscriber
 */
class ConfigEventsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::SAVE => 'configSave',
    ];
  }

  /**
   * React to a config object being saved.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   Config crud event.
   */
  public function configSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    $module_handler = \Drupal::moduleHandler();
    // Update views display_options's style type as cohesion_layout and
    // option's views_template & master_template if acquia_cms_site_studio
    // module is present else use default one.
    if ($module_handler->moduleExists('acquia_cms_site_studio')) {
      switch ($config->getName()) {
        case 'views.view.event_cards':
          \Drupal::classResolver(SearchFacade::class)->updateViewDisplayOptionsStyle('event_cards', 'upcoming_events_block', 'view_tpl_event_cards_slider');
          break;

        case 'views.view.article_cards':
          \Drupal::classResolver(SearchFacade::class)->updateViewDisplayOptionsStyle('article_cards', 'recent_articles_block', 'view_tpl_article_cards_slider');
          break;
      }
    }
    if ($module_handler->moduleExists('acquia_cms_site_studio') && $module_handler->moduleExists('acquia_cms_search')) {
      switch ($config->getName()) {
        case 'views.view.articles':
          \Drupal::classResolver(SearchFacade::class)->updateViewDisplayOptionsStyle('articles');
          break;

        case 'views.view.articles_fallback':
          \Drupal::classResolver(SearchFacade::class)->updateViewDisplayOptionsStyle('articles_fallback');
          break;

        case 'views.view.events':
          \Drupal::classResolver(SearchFacade::class)->updateViewDisplayOptionsStyle('events');
          break;

        case 'views.view.events_fallback':
          \Drupal::classResolver(SearchFacade::class)->updateViewDisplayOptionsStyle('events_fallback');
          break;

        case 'views.view.places':
          \Drupal::classResolver(SearchFacade::class)->updateViewDisplayOptionsStyle('places');
          break;

        case 'views.view.places_fallback':
          \Drupal::classResolver(SearchFacade::class)->updateViewDisplayOptionsStyle('places_fallback');
          break;

        case 'views.view.people':
          \Drupal::classResolver(SearchFacade::class)->updateViewDisplayOptionsStyle('people');
          break;

        case 'views.view.people_fallback':
          \Drupal::classResolver(SearchFacade::class)->updateViewDisplayOptionsStyle('people_fallback');
          break;

        case 'views.view.search':
          \Drupal::classResolver(SearchFacade::class)->updateViewDisplayOptionsStyle('search');
          break;

        case 'views.view.search_fallback':
          \Drupal::classResolver(SearchFacade::class)->updateViewDisplayOptionsStyle('search_fallback');
          break;
      }
    }
  }

}
