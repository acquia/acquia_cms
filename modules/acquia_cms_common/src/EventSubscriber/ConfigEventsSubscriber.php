<?php

namespace Drupal\acquia_cms_common\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Update views configuration based on modules' availability.
 *
 * @package Drupal\acquia_cms_common\EventSubscriber
 */
class ConfigEventsSubscriber implements EventSubscriberInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new ConfigEventsSubscriber object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The ModuleHandlerInterface.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

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
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function configSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    // Update views display_options's style type as cohesion_layout and
    // option's views_template & master_template if acquia_cms_site_studio
    // module is present else use default one.
    if ($this->moduleHandler->moduleExists('acquia_cms_site_studio')) {
      switch ($config->getName()) {
        case 'views.view.event_cards':
          _acquia_cms_common_update_view_display_options_style('event_cards', 'past_events_block', 'view_tpl_event_cards_slider');
          _acquia_cms_common_update_view_display_options_style('event_cards', 'upcoming_events_block', 'view_tpl_event_cards_slider');
          break;

        case 'views.view.article_cards':
          _acquia_cms_common_update_view_display_options_style('article_cards', 'recent_articles_block', 'view_tpl_article_cards_slider');
          break;
      }
    }
    if ($this->moduleHandler->moduleExists('acquia_cms_site_studio') && $this->moduleHandler->moduleExists('acquia_cms_search')) {
      switch ($config->getName()) {
        case 'views.view.articles':
          _acquia_cms_common_update_view_display_options_style('articles');
          break;

        case 'views.view.articles_fallback':
          _acquia_cms_common_update_view_display_options_style('articles_fallback');
          break;

        case 'views.view.events':
          _acquia_cms_common_update_view_display_options_style('events');
          break;

        case 'views.view.events_fallback':
          _acquia_cms_common_update_view_display_options_style('events_fallback');
          break;

        case 'views.view.places':
          _acquia_cms_common_update_view_display_options_style('places');
          break;

        case 'views.view.places_fallback':
          _acquia_cms_common_update_view_display_options_style('places_fallback');
          break;

        case 'views.view.people':
          _acquia_cms_common_update_view_display_options_style('people', 'default', 'view_tpl_people_grid');
          break;

        case 'views.view.people_fallback':
          _acquia_cms_common_update_view_display_options_style('people_fallback');
          break;

        case 'views.view.search':
          _acquia_cms_common_update_view_display_options_style('search');
          break;

        case 'views.view.search_fallback':
          _acquia_cms_common_update_view_display_options_style('search_fallback');
          break;
      }
    }
  }

}
