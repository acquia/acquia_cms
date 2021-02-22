<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to configure SiteStudioCore.
 */
final class SiteStudioCoreForm extends ConfigFormBase {

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
   * Constructs a new SiteStudioCoreForm.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info file parser.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, LinkGeneratorInterface $link_generator, InfoParserInterface $info_parser, StateInterface $state) {
    $this->module_handler = $module_handler;
    $this->linkGenerator = $link_generator;
    $this->infoParser = $info_parser;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('link_generator'),
      $container->get('info_parser'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_site_studio_core_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cohesion.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = FALSE;
    $module = 'cohesion';
    if ($this->module_handler->moduleExists($module)) {
      $module_path = $this->module_handler->getModule($module)->getPathname();
      $module_info = $this->infoParser->parse($module_path);
      $state_var = $this->getProgressState();
      if (isset($state_var['count']) && $state_var['count']) {
        $form['acquia_telemetry']['check_icon'] = [
          '#prefix' => '<span class= "dashboard-check-icon">',
          '#suffix' => "</span>",
        ];
      }
      $form[$module] = [
        '#type' => 'details',
        '#title' => $module_info['name'],
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];
      $form[$module]['api_key'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('API key'),
        '#placeholder' => '1234abcd',
        '#default_value' => $this->config('cohesion.settings')->get('api_key'),
        '#prefix' => '<div class= "dashboard-fields-wrapper">' . $module_info['description'],
      ];
      $form[$module]['agency_key'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Agency key'),
        '#placeholder' => '1234abcd',
        '#default_value' => $this->config('cohesion.settings')->get('organization_key'),
        '#suffix' => "</div>",
      ];
      $form[$module]['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => 'Save',
        '#submit' => ['::saveConfig'],
        '#prefix' => '<div class= "dashboard-buttons-wrapper">',
      ];
      $form[$module]['actions']['ignore'] = [
        '#type' => 'submit',
        '#value' => 'Ignore',
        '#submit' => ['::ignoreConfig'],
      ];
      $form[$module]['actions']['advanced'] = [
        '#markup' => $this->linkGenerator->generate(
            'Advanced',
            Url::fromRoute('cohesion.configuration.account_settings')
        ),
        '#suffix' => "</div>",
      ];

      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function saveConfig(array &$form, FormStateInterface $form_state) {
    $cohesion_api_key = $form_state->getValue(['api_key']);
    $cohesion_agency_key = $form_state->getValue(['agency_key']);
    $this->configFactory->getEditable('cohesion.settings')->set('api_key', $cohesion_api_key)->save();
    $this->configFactory->getEditable('cohesion.settings')->set('organization_key', $cohesion_agency_key)->save();
    $this->state->set('site_studio_progress', TRUE);
    $this->messenger()->addStatus('The configuration options have been saved.');
  }

  /**
   * {@inheritdoc}
   */
  public function ignoreConfig(array &$form, FormStateInterface $form_state) {
    $this->state->set('site_studio_progress', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getProgressState() {
    if ($this->module_handler->moduleExists('cohesion')) {
      return [
        'total' => 1,
        'count' => $this->state->get('site_studio_progress'),
      ];
    }
  }

}
