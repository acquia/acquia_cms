<?php

namespace Drupal\acquia_cms_tour\Controller;

use Drupal\acquia_cms_tour\AcquiaCmsTourManager;
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
   * The state interface.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The acquia cms tour manager.
   *
   * @var \Drupal\acquia_cms_tour\AcquiaCmsTourManager
   */
  protected $acquiaCmsTourManager;

  /**
   * Constructs a new ProgressBarForm.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Drupal\acquia_cms_tour\AcquiaCmsTourManager $acquia_cms_tour_manager
   *   The acquia cms tour manager class.
   */
  public function __construct(StateInterface $state, ClassResolverInterface $class_resolver, AcquiaCmsTourManager $acquia_cms_tour_manager) {
    $this->state = $state;
    $this->classResolver = $class_resolver;
    $this->acquiaCmsTourManager = $acquia_cms_tour_manager;
  }

  /**
   * Invokes a plugin and returns its output.
   *
   * @param string $plugin_class
   *   The plugin class.
   *
   * @return mixed
   *   The markup/output of the plugin class.
   */
  private function getSectionOutput(string $plugin_class) {
    if (is_a($plugin_class, 'Drupal\Core\Form\FormInterface', TRUE)) {
      return $this->formBuilder()->getForm($plugin_class);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('class_resolver'),
      $container->get('plugin.manager.acquia_cms_tour')
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

    // Delegate building each section using plugin class.
    $show = 0;
    foreach ($this->acquiaCmsTourManager->getDefinitions() as $definition) {
      $instance_definition = $this->classResolver->getInstanceFromDefinition($definition['class']);
      if ($instance_definition->isModuleEnabled()) {
        $total++;
        $show = 1;
        $build['wrapper'][$definition['id']] = $this->getSectionOutput($definition['class']);
        if ($instance_definition->getConfigurationState()) {
          $completed++;
        }
      }
    }
    if ($show == 1) {
      $form['help_text'] = [
        '#type' => 'markup',
        '#markup' => $this->t("ACMS organizes its features into individual components called modules.
        The configuration dashboard/wizard setup will help you setup the pre-requisites.
        Please note, not all modules in ACMS are required by default, and some optional modules
        are left disabled on install. A checklist is provided to help you keep track of the tasks
        needed to complete configuration."),
      ];
      $form['modal_link'] = [
        '#type' => 'link',
        '#title' => 'Wizard set-up',
        '#url' => $link_url,
      ];
      $form['show_progress'] = [
        '#type' => 'value',
        '#value' => TRUE,
      ];
    }
    else {
      $form['help_text'] = [
        '#type' => 'markup',
        '#markup' => $this->t("Please enable any of the following modules to be able to access the forms
        (ex. google_analytics, google_tag, gecoder, captcha, acquia_telemetry, cohesion)."),
      ];
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
