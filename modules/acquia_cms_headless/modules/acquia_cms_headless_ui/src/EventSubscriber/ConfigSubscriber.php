<?php

namespace Drupal\acquia_cms_headless_ui\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Menu\LocalTaskManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to config-related events.
 *
 * @internal
 *   This is an internal part of Acquia CMS Headless and may be changed or
 *   removed at any time without warning. External code should not extend or
 *   use this class in any way!
 */
final class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * The local task plugin manager.
   *
   * @var \Drupal\Core\Menu\LocalTaskManager
   */
  private $localTaskManager;

  /**
   * ConfigSubscriber constructor.
   *
   * @param \Drupal\Core\Menu\LocalTaskManager $local_task_manager
   *   The local task plugin manager.
   */
  public function __construct(LocalTaskManager $local_task_manager) {
    $this->localTaskManager = $local_task_manager;
  }

  /**
   * Reacts when configuration is saved.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The event object.
   */
  public function onSave(ConfigCrudEvent $event) {
    $name = $event->getConfig()->getName();

    if ($name === 'media.settings' && $event->isChanged('standalone_url')) {
      $this->localTaskManager->clearCachedDefinitions();
    }
    // Prevent system front page to get overridden
    // if pure headless mode is on.
    if ($name === 'system.site') {
      $this->updatePageConfigurations('system.site', [
        'page.front' => '/frontpage',
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ConfigEvents::SAVE => 'onSave',
    ];
  }

  /**
   * Helper function to update configuration for specified key.
   *
   * This is being used for updating page CT configurations.
   *
   * @param string $config_name
   *   The configuration name which needs to be updated.
   * @param array $configurations
   *   An array of drupal configurations.
   */
  function updatePageConfigurations(string $config_name, array $configurations) {
    $configFactory = \Drupal::service('config.factory');
    $config = $configFactory->getEditable($config_name);
    $need_save = FALSE;
    if ($config) {
      foreach ($configurations as $key => $value) {
        if ($config->get($key) != $value) {
          $config->set($key, $value);
          $need_save = TRUE;
        }
      }
      // Only save if there's changes in value.
      if ($need_save) {
        $config->save();
      }
    }
  }

}
