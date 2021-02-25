<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form to configure Acquia Solr Search module.
 */
final class AcquiaSearchSolrForm extends AcquiaCMSDashboardBase {

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'acquia_search_solr';

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
    if ($this->isModuleEnabled()) {
      $module_path = $this->module_handler->getModule($module)->getPathname();
      $module_info = $this->infoParser->parse($module_path);
      $api_host = $this->config('acquia_search_solr.settings')->get('api_host');
      $uuid = $this->state->get('acquia_search_solr.uuid');

      $configured = $this->getProgressState();
      if (!empty($api_host && $uuid)) {
        $configured = TRUE;
        $this->setState();
      }

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

    // Set dashboard state.
    $this->setState();
  }

  /**
   * {@inheritdoc}
   */
  public function ignoreConfig(array &$form, FormStateInterface $form_state) {
    $this->setState();
  }

}
