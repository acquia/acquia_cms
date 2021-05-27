<?php

namespace Drupal\acquia_cms_tour\Controller;

use Drupal\acquia_cms_tour\Form\AcquiaConnectorForm;
use Drupal\acquia_cms_tour\Form\AcquiaGoogleMapsApiDashboardForm;
use Drupal\acquia_cms_tour\Form\AcquiaSearchForm;
use Drupal\acquia_cms_tour\Form\AcquiaTelemetryForm;
use Drupal\acquia_cms_tour\Form\GoogleAnalyticsForm;
use Drupal\acquia_cms_tour\Form\GoogleTagManagerForm;
use Drupal\acquia_cms_tour\Form\RecaptchaForm;
use Drupal\acquia_cms_tour\Form\SiteStudioCoreForm;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
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
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The state interface.
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
    'acquia_search' => AcquiaSearchForm::class,
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
   * @param string $controller_class
   *   The class name.
   *
   * @return mixed
   *   The markup/output of the sub-controller.
   */
  private function getSectionOutput(string $controller_class) {
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
      '#type' => 'container',
      '#attributes' => [
        'class' => ['acms-dashboard-form-wrapper'],
      ],
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
    $total = 0;
    $completed = 0;

    $show_welcome_dialog = $this->state->get('show_welcome_modal', TRUE);
    $show_wizard_modal = $this->state->get('show_wizard_modal', TRUE);
    $wizard_completed = $this->state->get('wizard_completed', FALSE);
    $link_url = Url::fromRoute('acquia_cms_tour.welcome_modal_form');
    if (!$show_welcome_dialog) {
      $link_url = Url::fromRoute('acquia_cms_tour.installation_wizard');
    }
    $link_url->setOptions([
      'attributes' => [
        'class' => [
          'use-ajax',
          'button',
          'button--secondary',
          'button--small',
          'acms-dashboard-modal-form',
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 912,
          'dialogClass' => 'acms-installation-wizard',
        ]),
      ],
    ]);
    $form['help_text'] = [
      '#type' => 'markup',
      '#markup' => $this->t("ACMS organizes its features into individual components called modules.
       The configuration dashboard/wizard setup will help you setup the pre-requisties.
       Please note, not all modules in ACMS are required by default, and some optional modules
       are left disabled on install. A checklist is provided to help you keep track of the tasks
       needed to complete configuration."),
    ];
    $form['modal_link'] = [
      '#type' => 'link',
      '#title' => 'Wizard set-up',
      '#url' => $link_url,
    ];

    // Delegate building each section to sub-controllers, in order to keep all
    // extension-specific logic cleanly encapsulated.
    foreach (static::SECTIONS as $key => $controller) {
      $instance_definition = $this->classResolver->getInstanceFromDefinition($controller);
      if ($instance_definition->isModuleEnabled()) {
        $total++;
        $build['wrapper'][$key] = $this->getSectionOutput($controller);
        if ($instance_definition->getConfigurationState()) {
          $completed++;
        }
      }
    }
    $form['check_total']['#value'] = $total;
    $form['check_count']['#value'] = $completed;
    array_unshift($build, $form);

    // Attach acquia_cms_tour_dashboard library.
    $build['#attached'] = [
      'library' => [
        'acquia_cms_tour/acquia_cms_tour_dashboard',
      ],
      'drupalSettings' => [
        'show_wizard_modal' => $show_wizard_modal,
        'wizard_completed' => $wizard_completed,
      ],
    ];
    return $build;
  }

}
