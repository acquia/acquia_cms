<?php

namespace Drupal\acquia_cms_tour\Controller;

use Drupal\acquia_cms_tour\Form\AcquiaConnectorForm;
use Drupal\acquia_cms_tour\Form\AcquiaGoogleMapsApiDashboardForm;
use Drupal\acquia_cms_tour\Form\AcquiaSearchSolrForm;
use Drupal\acquia_cms_tour\Form\AcquiaTelemetryForm;
use Drupal\acquia_cms_tour\Form\GoogleAnalyticsForm;
use Drupal\acquia_cms_tour\Form\GoogleTagManagerForm;
use Drupal\acquia_cms_tour\Form\RecaptchaForm;
use Drupal\acquia_cms_tour\Form\SiteStudioCoreForm;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The sub-controllers to invoke in order to build the tour page.
   *
   * @var array
   */
  private const SECTIONS = [
    'site_studio_core_form' => SiteStudioCoreForm::class,
    'acquia_connector_form' => AcquiaConnectorForm::class,
    'acquia_solr_search_form' => AcquiaSearchSolrForm::class,
    'google_analytics_form' => GoogleAnalyticsForm::class,
    'acquia_google_maps_api' => AcquiaGoogleMapsApiDashboardForm::class,
    'recaptcha_form' => RecaptchaForm::class,
    'google_tag_manager_form' => GoogleTagManagerForm::class,
    'acquia_telemetry' => AcquiaTelemetryForm::class,
  ];

  /**
   * Constructs a new ProgressBarForm.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(StateInterface $state, ClassResolverInterface $class_resolver) {
    $this->state = $state;
    $this->classResolver = $class_resolver;
  }

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('class_resolver')
    );
  }

  /**
   * Returns a renderable array for a tour dashboard page.
   */
  public function content() {
    $build = [];
    $build['wrapper'] = [
      '#markup' => '',
      '#prefix' => '<div class = "acms-dashboard-form-wrapper">',
    ];
    $form = [];
    $form['#theme'] = 'acquia_cms_tour_checklist_form';
    $form['#attached']['library'][] = 'acquia_cms_tour/styling';
    $form['#tree'] = TRUE;
    // Set initial state of the checklist progress.
    $form['check_count'] = [
      '#type' => 'value',
      '#value' => 0,
    ];
    $form['check_total'] = [
      '#type' => 'value',
      '#value' => 0,
    ];
    $form['show_progress'] = [
      '#type' => 'value',
      '#value' => TRUE,
    ];
    $count = 0;
    $item_count = 0;
    foreach (static::SECTIONS as $key => $controller) {
      $count++;
      $build[$key] = $this->getSectionOutput($key, $controller);
      $state_var = $this->classResolver->getInstanceFromDefinition($controller)->getProgressState();
      if ($state_var) {
        $item_count++;
      }
    }
    $form['check_total']['#value'] = $count;
    $form['check_count']['#value'] = $item_count;
    array_unshift($build, $form);
    $build['wrapper_end'] = [
      '#markup' => '',
      '#suffix' => "</div>",
    ];
    return $build;
  }

}
