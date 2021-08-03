<?php

namespace Drupal\acquia_cms_common\EventSubscriber;

use Drupal\acquia_cms_search\Facade\SearchFacade;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
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
   * The ClassResolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolver
   */
  protected $searchFacade;

  /**
   * Constructs a new ConfigEventsSubscriber object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The ModuleHandlerInterface.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ClassResolverInterface $class_resolver) {
    $this->moduleHandler = $module_handler;
    $this->searchFacade = $class_resolver->getInstanceFromDefinition(SearchFacade::class);
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
   */
  public function configSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    // Update views display_options's style type as cohesion_layout and
    // option's views_template & master_template if acquia_cms_site_studio
    // module is present else use default one.
    if ($this->moduleHandler->moduleExists('acquia_cms_site_studio')) {
      switch ($config->getName()) {
        case 'views.view.event_cards':
          $this->searchFacade->updateViewDisplayOptionsStyle('event_cards', 'upcoming_events_block', 'view_tpl_event_cards_slider');
          break;

        case 'views.view.article_cards':
          $this->searchFacade->updateViewDisplayOptionsStyle('article_cards', 'recent_articles_block', 'view_tpl_article_cards_slider');
          break;
      }
    }
    if ($this->moduleHandler->moduleExists('acquia_cms_site_studio') && $this->moduleHandler->moduleExists('acquia_cms_search')) {
      switch ($config->getName()) {
        case 'views.view.articles':
          $this->searchFacade->updateViewDisplayOptionsStyle('articles');
          break;

        case 'views.view.articles_fallback':
          $this->searchFacade->updateViewDisplayOptionsStyle('articles_fallback');
          break;

        case 'views.view.events':
          $this->searchFacade->updateViewDisplayOptionsStyle('events');
          break;

        case 'views.view.events_fallback':
          $this->searchFacade->updateViewDisplayOptionsStyle('events_fallback');
          break;

        case 'views.view.places':
          $this->searchFacade->updateViewDisplayOptionsStyle('places');
          break;

        case 'views.view.places_fallback':
          $this->searchFacade->updateViewDisplayOptionsStyle('places_fallback');
          break;

        case 'views.view.people':
          $this->searchFacade->updateViewDisplayOptionsStyle('people');
          break;

        case 'views.view.people_fallback':
          $this->searchFacade->updateViewDisplayOptionsStyle('people_fallback');
          break;

        case 'views.view.search':
          $this->searchFacade->updateViewDisplayOptionsStyle('search');
          break;

        case 'views.view.search_fallback':
          $this->searchFacade->updateViewDisplayOptionsStyle('search_fallback');
          break;
      }
    }
  }

}
