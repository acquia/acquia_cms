<?php

namespace Drupal\acquia_cms_common\EventSubscriber;

use Drupal\acquia_cms_common\Services\AcmsUtilityService;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Installer\InstallerKernel;
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
   * The acms utility service.
   *
   * @var \Drupal\acquia_cms_common\Services\AcmsUtilityService
   */
  protected $acmsUtilityService;

  /**
   * Constructs a new ConfigEventsSubscriber object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The ModuleHandlerInterface.
   * @param \Drupal\acquia_cms_common\Services\AcmsUtilityService $acms_utility_service
   *   The acms utility service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, AcmsUtilityService $acms_utility_service) {
    $this->moduleHandler = $module_handler;
    $this->acmsUtilityService = $acms_utility_service;
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
    $moduleInstallTriggered = $this->acmsUtilityService->getModulePreinstallTriggered();
    if (InstallerKernel::installationAttempted() || $moduleInstallTriggered) {
      $config = $event->getConfig();
      // During site install or module install Update views display_options's
      // style type as cohesion_layout and option's views_template &
      // master_template if acquia_cms_site_studio module is present
      // otherwise use default one.
      if ($this->moduleHandler->moduleExists('acquia_cms_site_studio')) {
        switch ($config->getName()) {
          case 'views.view.event_cards':
            _acquia_cms_common_update_view_display_options_style('event_cards', 'past_events_block', 'view_tpl_event_cards_slider');
            _acquia_cms_common_update_view_display_options_style('event_cards', 'upcoming_events_block', 'view_tpl_event_cards_slider');
            break;

          case 'views.view.article_cards':
            _acquia_cms_common_update_view_display_options_style('article_cards', 'default', 'view_tpl_article_cards_slider');
            break;

          case 'field.field.node.page.body':
            _acquia_cms_common_update_page_configurations('field.field.node.page.body', [
              'label' => 'Search Description',
              'description' => 'A short description or teaser which will be displayed in search results.'
            ]);
            break;

          case 'core.entity_form_display.node.page.default':
            _acquia_cms_common_update_page_configurations('core.entity_form_display.node.page.default', [
              'content.field_layout_canvas' => [
                'type'=> 'cohesion_layout_builder_widget',
                'weight' => 2,
                'settings' => [],
                'third_party_settings' => [],
                'region' => 'content'
              ]
            ]);
            break;

          case 'core.entity_view_display.node.page.default':
            _acquia_cms_common_update_page_configurations('core.entity_view_display.node.page.default', [
              'content.field_layout_canvas' => [
                'type'=> 'cohesion_entity_reference_revisions_entity_view',
                'weight' => 2,
                'label' => 'hidden',
                'settings' => [
                  'view_mode' => 'default',
                  'link'  => false
                ],
                'third_party_settings' => [],
                'region' => 'content'
              ]
            ]);
            break;

          case 'core.entity_view_display.node.page.horizontal_card':
            _acquia_cms_common_update_page_configurations('core.entity_view_display.node.page.horizontal_card', [
              'content.field_layout_canvas' => [
                'type'=> 'cohesion_entity_reference_revisions_entity_view',
                'weight' => 2,
                'label' => 'hidden',
                'settings' => [
                  'view_mode' => 'default',
                  'link'  => false
                ],
                'third_party_settings' => [],
                'region' => 'content'
              ]
            ]);
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
      if ($this->moduleHandler->moduleExists('samlauth')) {
        if ($config->getName() == 'samlauth.authentication') {
          _acquia_cms_common_update_page_configurations('samlauth.authentication', [
            'map_users' => TRUE,
            'map_users_name' => TRUE,
            'map_users_mail' => TRUE,
            'map_users_roles' => [
              'administrator' => 'administrator',
              'developer' => 'developer',
              'content_administrator' => 'content_administrator',
              'content_author' => 'content_author',
              'content_editor' => 'content_editor',
              'user_administrator' => 'user_administrator',
            ]
          ]);
        }
      }
    } 
  }

}
