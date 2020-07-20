<?php

namespace Drupal\acquia_cms_tour\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Controller\ControllerResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a route controller providing a simple tour of Acquia CMS.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class TourController extends ControllerBase {

  /**
   * The sub-controllers to invoke in order to build the tour page.
   *
   * @var array
   */
  private const SECTIONS = [
    'google_analytics' => GoogleAnalytics::class . '::build',
    'google_tag_manager' => GoogleTagManager::class . '::build',
  ];

  /**
   * The controller resolver service.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  private $controllerResolver;

  /**
   * TourController constructor.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver service.
   */
  public function __construct(ControllerResolverInterface $controller_resolver) {
    $this->controllerResolver = $controller_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('controller_resolver')
    );
  }

  /**
   * Returns a renderable array for a tour page.
   */
  public function tour() {
    $tour = [];

    // Delegate building each section to sub-controllers, in order to keep all
    // extension-specific logic cleanly encapsulated.
    foreach (static::SECTIONS as $key => $controller) {
      $controller = $this->controllerResolver->getControllerFromDefinition($controller);
      if ($controller) {
        $tour[$key] = $controller();
      }
    }
    return $tour;
  }

}
