<?php

namespace Drupal\acquia_cms_search\Plugin\AcquiaCmsTour;

use Drupal\acquia_cms_tour\Form\AcquiaCMSDashboardBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the acquia_cms_tour.
 *
 * @AcquiaCmsTour(
 *   id = "acquia_search",
 *   label = @Translation("Acquia Search"),
 *   weight = 3
 * )
 */
class AcquiaSearchForm extends AcquiaCMSDashboardBase {

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'acquia_search';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_search_form';
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
    $module = $this->module;
    if ($this->isModuleEnabled()) {
      $module_path = $this->moduleHandler->getModule($module)->getPathname();
      $module_info = $this->infoParser->parse($module_path);
      $configured = $this->getConfigurationState();
      if ($configured) {
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
        '#required' => TRUE,
        '#title' => $this->t('Acquia Subscription identifier'),
        '#default_value' => $this->state->get('acquia_search.identifier'),
        '#description' => $this->t('Obtain this from the "Product Keys" section of the Acquia Cloud UI. Example: ABCD-12345'),
        '#prefix' => '<div class= "dashboard-fields-wrapper">' . $module_info['description'],
      ];
      $form[$module]['api_key'] = [
        '#type' => 'password',
        '#title' => $this->t('Acquia Connector key'),
        '#description' => $this->t('Obtain this from the "Product Keys" section of the Acquia Cloud UI.'),
      ];
      $form[$module]['api_host'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Acquia Search API hostname'),
        '#default_value' => $this->config('acquia_search.settings')->get('api_host'),
        '#description' => $this->t('API endpoint domain or URL. Default value is "https://api.sr-prod02.acquia.com".'),
      ];
      $form[$module]['uuid'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Acquia Application UUID'),
        '#default_value' => $this->state->get('acquia_search.uuid'),
        '#description' => $this->t('Obtain this from the "Product Keys" section of the Acquia Cloud UI.'),
        '#suffix' => "</div>",
      ];
      $form[$module]['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => 'Save',
        '#button_type' => 'primary',
        '#prefix' => '<div class= "dashboard-buttons-wrapper">',
      ];
      $form[$module]['actions']['ignore'] = [
        '#type' => 'submit',
        '#value' => 'Ignore',
        '#limit_validation_errors' => [],
        '#submit' => ['::ignoreConfig'],
      ];
      $form[$module]['actions']['advanced'] = [
        '#prefix' => '<div class= "dashboard-tooltiptext">',
        '#markup' => $this->linkGenerator->generate(
          'Advanced',
          Url::fromRoute('entity.search_api_server.edit_form', ['search_api_server' => 'acquia_search_server'])
        ),
        '#suffix' => "</div>",
      ];
      $form[$module]['actions']['advanced']['information'] = [
        '#prefix' => '<b class= "tool-tip__icon">i',
        '#suffix' => "</b>",
      ];
      $form[$module]['actions']['advanced']['tooltip-text'] = [
        '#prefix' => '<span class= "tooltip">',
        '#markup' => $this->t("Opens Advance Configuration in new tab"),
        '#suffix' => "</span></div>",
      ];
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $solr_identifier = $form_state->getValue(['identifier']);
    $solr_api_key = $form_state->getValue(['api_key']);
    $solr_api_host = $form_state->getValue(['api_host']);
    $solr_api_uuid = $form_state->getValue(['uuid']);
    $this->config('acquia_search.settings')->set('api_host', $solr_api_host)->save(TRUE);
    $this->state->set('acquia_search.identifier', $solr_identifier);
    $this->state->set('acquia_search.api_key', $solr_api_key);
    $this->state->set('acquia_search.uuid', $solr_api_uuid);
    $this->setConfigurationState();
    $this->messenger()->addStatus('The configuration options have been saved.');
  }

  /**
   * {@inheritdoc}
   */
  public function ignoreConfig(array &$form, FormStateInterface $form_state) {
    $this->setConfigurationState();
  }

  /**
   * {@inheritdoc}
   */
  public function checkMinConfiguration() {
    $api_host = $this->config('acquia_search.settings')->get('api_host');
    $solr_identifier = $this->state->get('acquia_search.identifier');
    $api_key = $this->state->get('acquia_search.api_key');
    $uuid = $this->state->get('acquia_search.uuid');
    return !empty($api_host) && !empty($uuid) && !empty($api_key) && !empty($solr_identifier);
  }

}
