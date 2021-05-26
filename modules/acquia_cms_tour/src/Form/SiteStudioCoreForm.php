<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form to configure SiteStudioCore.
 */
final class SiteStudioCoreForm extends AcquiaCMSDashboardBase {

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'cohesion';

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
    $module = $this->module;
    if ($this->isModuleEnabled()) {
      $module_path = $this->module_handler->getModule($module)->getPathname();
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cohesion_api_key = $form_state->getValue(['api_key']);
    $cohesion_agency_key = $form_state->getValue(['agency_key']);
    if ($cohesion_api_key && $cohesion_agency_key) {
      $this->config('cohesion.settings')->set('api_key', $cohesion_api_key)->save();
      $this->config('cohesion.settings')->set('organization_key', $cohesion_agency_key)->save();
    }
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
    $api_key = $this->config('cohesion.settings')->get('api_key');
    $agency_key = $this->config('cohesion.settings')->get('organization_key');
    return $api_key &&  $agency_key;
  }

}
