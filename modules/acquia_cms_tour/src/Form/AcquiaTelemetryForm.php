<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to toggle the Acquia Telemetry module.
 */
final class AcquiaTelemetryForm extends ConfigFormBase {
  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  private $moduleInstaller;
  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The info file parser.
   *
   * @var \Drupal\Core\Extension\InfoParserInterface
   */
  protected $infoParser;

  /**
   * AcquiaTelemetryForm constructor.
   *
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The module installer.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info file parser.
   */
  public function __construct(ModuleInstallerInterface $module_installer, StateInterface $state, ModuleHandlerInterface $module_handler, LinkGeneratorInterface $link_generator, InfoParserInterface $info_parser) {
    $this->moduleInstaller = $module_installer;
    $this->state = $state;
    $this->module_handler = $module_handler;
    $this->linkGenerator = $link_generator;
    $this->infoParser = $info_parser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_installer'),
      $container->get('state'),
      $container->get('module_handler'),
      $container->get('link_generator'),
      $container->get('info_parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_telemetry_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'acquia_telemetry.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = FALSE;
    $module = 'acquia_telemetry';
    if ($this->module_handler->moduleExists($module)) {
      $module_path = $this->module_handler->getModule($module)->getPathname();
      $module_info = $this->infoParser->parse($module_path);
      if ($this->getProgressState()) {
        $form['acquia_telemetry']['check_icon'] = [
          '#prefix' => '<span class= "dashboard-check-icon">',
          '#suffix' => "</span>",
        ];
      }
      $form['acquia_telemetry_wrapper'] = [
        '#type' => 'details',
        '#title' => $this->t('Acquia Telemetry'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      $form['acquia_telemetry_wrapper'][$module] = [
        'opt_in' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Send anonymous data about Acquia product usage'),
          '#default_value' => 1,
          '#description' => $this->t('This module intends to collect anonymous data about Acquia product usage. No private information will be gathered. Data will not be used for marketing or sold to any third party. This is an opt-in module and can be disabled at any time by uninstalling the acquia_telemetry module by your site administrator.'),
          '#prefix' => '<div class= "dashboard-fields-wrapper">',
          '#suffix' => "</div>",
        ],
      ];
      $form['acquia_telemetry_wrapper'][$module]['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => 'Save',
        '#submit' => ['::saveConfig'],
        '#prefix' => '<div class= "dashboard-buttons-wrapper">',
      ];
      $form['acquia_telemetry_wrapper'][$module]['actions']['ignore'] = [
        '#type' => 'submit',
        '#value' => 'Ignore',
        '#submit' => ['::ignoreConfig'],
      ];
      if (isset($module_info['configure'])) {
        $form['acquia_telemetry_wrapper'][$module]['actions']['advanced'] = [
          '#markup' => $this->linkGenerator->generate(
            'Advanced',
            Url::fromRoute($module_info['configure'])
          ),
          '#suffix' => "</div>",
        ];
      }

      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function saveConfig(array &$form, FormStateInterface $form_state) {
    // Enable the Acquia Telemetry module if user opts in.
    $acquia_telemetry_opt_in = $form_state->getValue('opt_in');

    if ($acquia_telemetry_opt_in) {
      $this->moduleInstaller->install(['acquia_telemetry']);
      $this->state->set('acquia_telemetry_progress', TRUE);
      $this->messenger()->addStatus('You have opted into Acquia Telemetry. Thank you for helping improve Acquia products.');
    }
    else {
      $this->moduleInstaller->uninstall(['acquia_telemetry']);
      $this->messenger()->addStatus('You have successfully opted out of Acquia Telemetry. Anonymous usage information will no longer be collected.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function ignoreConfig(array &$form, FormStateInterface $form_state) {
    $this->state->set('acquia_telemetry_progress', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getProgressState() {
    if ($this->module_handler->moduleExists('acquia_telemetry')) {
      return [
        'total' => 1,
        'count' => $this->state->get('acquia_telemetry_progress'),
      ];
    }
  }

}
