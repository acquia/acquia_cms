<?php

namespace Drupal\acquia_cms_tour\Controller;

use Drupal\acquia_cms_tour\Form\AcquiaTelemetryForm;
use Drupal\checklistapi\Form\ChecklistapiChecklistForm;
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
    'dashboard_checklist' => ChecklistapiChecklistForm::class,
    'acquia_telemetry' => AcquiaTelemetryForm::class,
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
      if ($key === 'dashboard_checklist') {
        return [
          '#type' => 'details',
          '#open' => TRUE,
          '#title' => $this->t('Dashboard Checklist'),
          $this->formBuilder()->getForm($controller_class, 'tour_dashboard', 'any', ['TRUE']),
        ];
      }
      return $this->formBuilder()->getForm($controller_class);
    }
  }

  /**
   * Returns a renderable array for a tour dashboard page.
   */
  public function tour() {
    $tour = [];

    // Delegate building each section to sub-controllers, in order to keep all
    // extension-specific logic cleanly encapsulated.
    foreach (static::SECTIONS as $key => $controller) {
      $tour[$key] = $this->getSectionOutput($key, $controller);
    }
    return $tour;
  }

}
