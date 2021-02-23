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
 * Provides a form to configure Acquia Solr Search module.
 */
final class AcquiaSearchSolrForm extends ConfigFormBase implements AcquiaDashboardInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'acquia_search_solr';

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
   * Constructs a new AcquiaSearchSolrForm.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info file parser.
   */
  public function __construct(StateInterface $state, ModuleHandlerInterface $module_handler, LinkGeneratorInterface $link_generator, InfoParserInterface $info_parser) {
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
    return 'acquia_cms_solr_search_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'acquia_search_solr.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = FALSE;
    $module = $this->module;
    if ($this->module_handler->moduleExists($module)) {
      $module_path = $this->module_handler->getModule($module)->getPathname();
      $module_info = $this->infoParser->parse($module_path);
      $api_host = $this->config('acquia_search_solr.settings')->get('api_host');
      $uuid = $this->state->get('acquia_search_solr.uuid');
      if (!empty($api_host && $uuid)) {
        $this->state->set('acquia_search_solr_progress', TRUE);
      }
      if ($this->state->get('acquia_search_solr_progress')) {
        $form['check_icon'] = [
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

      $form[$module]['identifier'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Acquia Subscription identifier'),
        '#placeholder' => 'ABCD-1234',
        '#default_value' => $this->state->get('acquia_search_solr.identifier'),
        '#prefix' => '<div class= "dashboard-fields-wrapper">' . $module_info['description'],
      ];
      $form[$module]['api_host'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Acquia Search API hostname'),
        '#placeholder' => 'https://api.example.com',
        '#default_value' => $this->config('acquia_search_solr.settings')->get('api_host'),
      ];
      $form[$module]['uuid'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Acquia Application UUID'),
        '#placeholder' => 'abcd-1234',
        '#default_value' => $this->state->get('acquia_search_solr.uuid'),
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
      if (isset($module_info['configure'])) {
        $form[$module]['actions']['advanced'] = [
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#value'] == 'Save') {
      $solr_identifier = $form_state->getValue(['identifier']);
      $solr_api_host = $form_state->getValue(['api_host']);
      $solr_api_uuid = $form_state->getValue(['uuid']);
      if (empty($solr_identifier)) {
        $form_state->setErrorByName('identifier', $this->t('Acquia Subscription identifier is required.'));
      }
      if (empty($solr_api_host)) {
        $form_state->setErrorByName('api_host', $this->t('Acquia Search API hostname is required.'));
      }
      if (empty($solr_api_uuid)) {
        $form_state->setErrorByName('uuid', $this->t('Acquia Application UUID is required.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function saveConfig(array &$form, FormStateInterface $form_state) {
    $solr_identifier = $form_state->getValue(['identifier']);
    $solr_api_host = $form_state->getValue(['api_host']);
    $solr_api_uuid = $form_state->getValue(['uuid']);
    $this->config('acquia_search_solr.settings')->set('api_host', $solr_api_host)->save(TRUE);
    $this->state->set('acquia_search_solr.identifier', $solr_identifier);
    $this->state->set('acquia_search_solr.uuid', $solr_api_uuid);
    $this->state->set('acquia_search_solr_progress', TRUE);
    $this->messenger()->addStatus('The configuration options have been saved.');
  }

  /**
   * {@inheritdoc}
   */
  public function ignoreConfig(array &$form, FormStateInterface $form_state) {
    $this->state->set('acquia_search_solr_progress', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleStatus() {
    if ($this->module_handler->moduleExists($this->module)) {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getProgressState() {
    if ($this->module_handler->moduleExists($this->module)) {
      return $this->state->get('acquia_search_solr_progress');
    }
  }

}
