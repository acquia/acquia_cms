<?php

namespace Drupal\acquia_cms\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\State\State;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * ThemeNegotiator class for switching based on certain condition.
 */
class ThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * The system theme config object.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Constructs a DefaultNegotiator object.
   *
   * @param \Drupal\Core\State\State $state
   *   The object State.
   */
  public function __construct(State $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if ($route_match->getRouteName() == "system.db_update" || $this->state->get('system.maintenance_mode')) {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return "acquia_claro";
  }

}
