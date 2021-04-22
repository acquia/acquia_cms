<?php

namespace Drupal\acquia_cms_tour\Form;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\acquia_cms_tour\Services\AcquiaCloudService;
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
final class AcquiaSearchForm extends ConfigFormBase {

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
   * The acquia cloud service.
   *
   * @var \Drupal\acquia_cms_tour\Services\AcquiaCloudService
   */
  protected $acquiaCloudService;

  /**
   * Constructs a new AcquiaSearchForm.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info file parser.
   * @param \Drupal\acquia_cms_tour\Services\AcquiaCloudService $acquia_cloud_service
   *   The acquia cloud platform service.
   */
  public function __construct(
    StateInterface $state,
    ModuleHandlerInterface $module_handler,
    LinkGeneratorInterface $link_generator,
    InfoParserInterface $info_parser,
    AcquiaCloudService $acquia_cloud_service
  ) {
    $this->state = $state;
    $this->moduleHandler = $module_handler;
    $this->linkGenerator = $link_generator;
    $this->infoParser = $info_parser;
    $this->acquiaCloudService = $acquia_cloud_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('module_handler'),
      $container->get('link_generator'),
      $container->get('info_parser'),
      $container->get('acquia_cms_tour.cloud_service')
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
      'acquia_search.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = FALSE;
    $module = 'acquia_search';
    if ($this->moduleHandler->moduleExists($module)) {
      $module_path = $this->moduleHandler->getModule($module)->getPathname();
      $module_info = $this->infoParser->parse($module_path);
      $form['acquia_search'] = [
        '#type' => 'fieldset',
        '#title' => $module_info['name'],
        '#description' => $module_info['description'],
        '#open' => TRUE,
      ];
      $form['acquia_search']['identifier'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Acquia Subscription identifier'),
        '#default_value' => $this->state->get('acquia_search.identifier'),
        '#description' => $this->t('Obtain this from the "Product Keys" section of the Acquia Cloud UI. Example: ABCD-12345'),
      ];
      $form['acquia_search']['api_key'] = [
        '#type' => 'password',
        '#title' => $this->t('Acquia Connector key'),
        '#description' => $this->t('Obtain this from the "Product Keys" section of the Acquia Cloud UI.'),
        '#field_suffix' => $this->state->get('acquia_search.identifier') ? '***' : '',
      ];
      $form['acquia_search']['api_host'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Acquia Search API hostname'),
        '#default_value' => $this->config('acquia_search.settings')->get('api_host'),
        '#description' => $this->t('API endpoint domain or URL. Default value is "https://api.sr-prod02.acquia.com".'),
      ];
      $form['acquia_search']['uuid'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Acquia Application UUID'),
        '#default_value' => $this->state->get('acquia_search.uuid'),
        '#description' => $this->t('Obtain this from the "Product Keys" section of the Acquia Cloud UI.'),
      ];
      $form['acquia_search']['cloud_api_key'] = [
        '#type' => 'password',
        '#title' => $this->t('Acquia API key'),
        '#default_value' => $this->state->get('acquia_search.cloud_api_key'),
        '#field_suffix' => $this->state->get('acquia_search.cloud_api_key') ? '***' : '',
        '#description' => $this->t('Obtain this from <a href="@api_tokens">API Token</a> section of the Acquia Cloud UI.', [
          '@api_tokens' => 'https://cloud.acquia.com/a/profile/tokens',
        ]),
      ];
      $form['acquia_search']['cloud_api_secret'] = [
        '#type' => 'password',
        '#title' => $this->t('Acquia API secret'),
        '#default_value' => $this->state->get('acquia_search.cloud_api_secret'),
        '#field_suffix' => $this->state->get('acquia_search.cloud_api_secret') ? '***' : '',
        '#description' => $this->t('Obtain this from <a href="@api_tokens">API Token</a> section of the Acquia Cloud UI.', [
          '@api_tokens' => 'https://cloud.acquia.com/a/profile/tokens',
        ]),
      ];
      $form['acquia_search']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => 'Save',
        '#button_type' => 'primary',
      ];
      $form['acquia_search']['actions']['advanced'] = [
        '#markup' => $this->linkGenerator->generate(
          'Advanced',
          Url::fromRoute('entity.search_api_server.edit_form', ['search_api_server' => 'acquia_search_server'])
        ),
        '#prefix' => '<span class= "button advanced-button">',
        '#suffix' => "</span>",
      ];
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $solr_api_key = $form_state->getValue(['api_key']);
    $cloud_api_key = $form_state->getValue(['cloud_api_key']);
    $cloud_api_secret = $form_state->getValue(['cloud_api_secret']);
    if (!$solr_api_key && !$this->state->get('acquia_search.api_key')) {
      $form_state->setErrorByName('api_key', $this->t('Acquia Connector key is required.'));
    }
    if (!$cloud_api_key && !$this->state->get('acquia_search.cloud_api_key')) {
      $form_state->setErrorByName('cloud_api_key', $this->t('Acquia API key is required.'));
    }
    if (!$cloud_api_secret && !$this->state->get('acquia_search.cloud_api_secret')) {
      $form_state->setErrorByName('cloud_api_secret', $this->t('Acquia API Secret is required.'));
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $solr_identifier = $form_state->getValue(['identifier']);
    $solr_api_key = $form_state->getValue(['api_key']);
    $solr_api_host = $form_state->getValue(['api_host']);
    $solr_api_uuid = $form_state->getValue(['uuid']);
    $cloud_api_key = $form_state->getValue(['cloud_api_key']);
    $cloud_api_secret = $form_state->getValue(['cloud_api_secret']);
    $this->config('acquia_search.settings')->set('api_host', $solr_api_host)->save(TRUE);
    $this->state->set('acquia_search.identifier', $solr_identifier);
    $this->state->set('acquia_search.uuid', $solr_api_uuid);
    // Since this is non required field, save ony if value is provided.
    if ($solr_api_key) {
      $this->state->set('acquia_search.api_key', $solr_api_key);
    }
    if ($cloud_api_key) {
      $this->state->set('acquia_search.cloud_api_key', $cloud_api_key);
    }
    if ($cloud_api_secret) {
      $this->state->set('acquia_search.cloud_api_secret', $cloud_api_secret);
    }
    // Create search index if not already created.
    if (AcquiaDrupalEnvironmentDetector::isAhEnv()) {
      $env_name = AcquiaDrupalEnvironmentDetector::getAhEnv();
      if ($env_name) {
        $this->acquiaCloudService->createSearchIndex($env_name);
      }
    }
    $this->messenger()->addStatus('The configuration options have been saved.');
  }

}
