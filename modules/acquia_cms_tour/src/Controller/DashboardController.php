<?php

namespace Drupal\acquia_cms_tour\Controller;

use Drupal\acquia_cms_tour\AcquiaCmsTourManager;
use Drupal\acquia_cms_tour\Services\StarterKitService;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The acquia cms tour manager.
   *
   * @var \Drupal\acquia_cms_tour\AcquiaCmsTourManager
   */
  protected $acquiaCmsTourManager;

  /**
   * The acquia cms tour manager.
   *
   * @var \Drupal\acquia_cms_tour\Services\StarterKitService
   */
  protected $starterKitService;

  /**
   * Constructs a new ProgressBarForm.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The class resolver.
   * @param \Drupal\acquia_cms_tour\AcquiaCmsTourManager $acquia_cms_tour_manager
   *   The acquia cms tour manager.
   * @param \Drupal\acquia_cms_tour\Services\StarterKitService $starter_kit_service
   *   The class resolver.
   */
  public function __construct(
  StateInterface $state,
  ClassResolverInterface $class_resolver,
  RequestStack $request_stack,
  AcquiaCmsTourManager $acquia_cms_tour_manager,
  StarterKitService $starter_kit_service
  ) {
    $this->state = $state;
    $this->classResolver = $class_resolver;
    $this->requestStack = $request_stack;
    $this->acquiaCmsTourManager = $acquia_cms_tour_manager;
    $this->starterKitService = $starter_kit_service;
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
      $container->get('request_stack'),
      $container->get('plugin.manager.acquia_cms_tour'),
      $container->get('acquia_cms_tour.starter_kit')
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
    $existing_site_acquia_cms = $this->state->get('existing_site_acquia_cms', FALSE);
    $show_starter_kit_modal = $this->requestStack->getCurrentRequest()->get('show_starter_kit_modal') ?? FALSE;
    $show_welcome_dialog = $this->state->get('show_welcome_modal', TRUE);
    $show_wizard_modal = $this->state->get('show_wizard_modal', TRUE);
    $wizard_completed = $this->state->get('wizard_completed', FALSE);
    $starter_kit_wizard_completed = $this->state->get('starter_kit_wizard_completed', FALSE);
    $selected_starter_kit = $this->state->get('acquia_cms.starter_kit');
    $hide_starter_kit_intro_dialog = $this->state->get('hide_starter_kit_intro_dialog');
    $starter_link_url = Url::fromRoute('acquia_cms_tour.starter_kit_welcome_modal_form');
    $link_url = Url::fromRoute('acquia_cms_tour.welcome_modal_form');
    $acquia_cms_enterprise_low_code = $this->starterKitService->getMissingModules('acquia_cms_enterprise_low_code');
    $acquia_cms_community = $this->starterKitService->getMissingModules('acquia_cms_community');
    $acquia_cms_headless = $this->starterKitService->getMissingModules('acquia_cms_headless');
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
    if (!$existing_site_acquia_cms && !$starter_kit_wizard_completed) {
      $starter_link_url->setOptions([
        'attributes' => [
          'class' => [
            'use-ajax',
            'button',
            'button--secondary',
            'button--small',
            'acms-starterkit-modal-form',
          ],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 912,
            'dialogClass' => 'acms-starter-kit-wizard',
          ]),
        ],
      ]);
      $form['starter_modal_link'] = [
        '#type' => 'link',
        '#title' => $this->t('Starter kit set-up'),
        '#url' => $starter_link_url,
      ];
    }

    // Delegate building each section using plugin class.
    foreach ($this->acquiaCmsTourManager->getDefinitions() as $definition) {
      $instance_definition = $this->classResolver->getInstanceFromDefinition($definition['class']);
      if ($instance_definition->isModuleEnabled()) {
        $total++;
        $build['wrapper'][$definition['id']] = $this->getSectionOutput($definition['class']);
        if ($instance_definition->getConfigurationState()) {
          $completed++;
        }
      }
    }

    if ($total > 0) {
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
        '#title' => $this->t('Wizard set-up'),
        '#url' => $link_url,
      ];
    }
    else {
      $form['help_text'] = [
        '#type' => 'markup',
        '#markup' => $this->t("<i><h3>It seems like you have installed minimal Acquia CMS, which does not have any specific configurations. You are all set. Once you enable any of the modules supported by the wizard, they should start appearing here.
        (ex. google_analytics, gecoder, recaptcha, acquia_telemetry, cohesion etc.).</h3></i>"),
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
        'hide_starter_kit_wizard_modal' => $hide_starter_kit_intro_dialog,
        'wizard_completed' => $wizard_completed,
        'selected_starter_kit' => $selected_starter_kit,
        'show_starter_kit_modal' => $show_starter_kit_modal,
        'existing_site_acquia_cms' => $existing_site_acquia_cms,
        'acquia_cms_enterprise_low_code' => $acquia_cms_enterprise_low_code,
        'acquia_cms_community' => $acquia_cms_community,
        'acquia_cms_headless' => $acquia_cms_headless,
      ],
    ];
    return $build;
  }

}
