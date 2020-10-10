<?php

namespace Drupal\acquia_cms_tour\Controller;

use Drupal\acquia_cms_tour\Form\AcquiaCmsToolChecklistForm;
use Drupal\acquia_cms_tour\Form\AcquiaGoogleMapsAPIForm;
use Drupal\acquia_cms_tour\Form\AcquiaTelemetryForm;
use Drupal\acquia_cms_tour\Form\GoogleApiChecklistForm;
use Drupal\Core\Controller\ControllerBase;

/**
 * Defines a route controller providing a simple tour dashboard of Acquia CMS.
 *
 * @internal
 *   This is a totally internal part of Acquia CMS and may be changed in any
 *   way, or removed outright, at any time without warning. External code should
 *   not use this class!
 */
final class DashboardController extends ControllerBase {

  /**
   * The sub-controllers to invoke in order to build the tour page.
   *
   * @var array
   */
  private const SECTIONS = [
    'acquia_telemetry' => AcquiaTelemetryForm::class,
    'acquia_google_maps_api' => AcquiaGoogleMapsAPIForm::class,
    'acquia_cms_tool_checklist' => AcquiaCmsToolChecklistForm::class,
    'google_api_checklist' => GoogleApiChecklistForm::class,
  ];

  /**
   * Invokes a sub-controller and returns its output.
   *
   * @param string $key
   *   The key.
   * @param string $controller_class
   *   The class name.
   *
   * @return mixed
   *   The markup/output of the sub-controller.
   */
  private function getSectionOutput(string $key, string $controller_class) {
    if (is_a($controller_class, 'Drupal\Core\Form\FormInterface', TRUE)) {
      return $this->formBuilder()->getForm($controller_class);
    }
  }

  /**
   * Returns a renderable array for a tour dashboard page.
   */
  public function content() {
    $build = [];

    // Delegate building each section to sub-controllers, in order to keep all
    // extension-specific logic cleanly encapsulated.
    foreach (static::SECTIONS as $key => $controller) {
      $build[$key] = $this->getSectionOutput($key, $controller);
    }

    return $build;
  }

}
