<?php

namespace Drupal\acquia_starterkit_core\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Recipe\RecipeAppliedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Track recipes applied.
 *
 * @package Drupal\acquia_starterkit_core\EventSubscriber
 */
class RecipeAppliedEventSubscriber implements EventSubscriberInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * RecipeAppliedEventsSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      RecipeAppliedEvent::class => 'onRecipeApply',
    ];
  }

  /**
   * Track the Acquia Starter Kit Recipes.
   *
   * @param \Drupal\Core\Recipe\RecipeAppliedEvent $event
   *   The recipe applied event.
   */
  public function onRecipeApply(RecipeAppliedEvent $event): void {
    $exploded = explode('/', $event->recipe->path);
    $recipe_name = end($exploded);
    if (str_starts_with($recipe_name, 'acquia_')) {
      $this->saveRecipeApplied($recipe_name);
    }
  }

  /**
   * Save the recipe name in acquia_starterkit_core.settings config.
   *
   * @param string $recipe_name
   *   The recipes name.
   */
  protected function saveRecipeApplied(string $recipe_name): void {
    $config = $this->config->getEditable('acquia_starterkit_core.settings');
    $recipes = $config->get('recipes_applied') ?: [];
    $recipes[] = $recipe_name;
    $config->set('recipes_applied', $recipes)->save();
  }

}
