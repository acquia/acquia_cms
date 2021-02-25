<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form to configure the Recaptcha module.
 */
final class RecaptchaForm extends AcquiaCMSDashboardBase {

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'recaptcha';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_recaptcha_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'recaptcha.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = FALSE;
    $module = $this->module;
    if ($this->isModuleEnabled()) {
      $site_key = $this->config('recaptcha.settings')->get('site_key');
      $secret_key = $this->config('recaptcha.settings')->get('secret_key');

      $configured = $this->getConfigurationState();
      if (!empty($site_key && $secret_key)) {
        $configured = TRUE;
        $this->setConfigurationState();
      }

      if ($configured) {
        $form['check_icon'] = [
          '#prefix' => '<span class= "dashboard-check-icon">',
          '#suffix' => "</span>",
        ];
      }
      $module_path = $this->module_handler->getModule($module)->getPathname();
      $module_info = $this->infoParser->parse($module_path);
      $form[$module] = [
        '#type' => 'details',
        '#title' => $module_info['name'],
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];
      $form[$module]['site_key'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Site key'),
        '#placeholder' => '1234abcd',
        '#default_value' => $this->config('recaptcha.settings')->get('site_key'),
        '#prefix' => '<div class= "dashboard-fields-wrapper">' . $module_info['description'],
      ];
      $form[$module]['secret_key'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Secret key'),
        '#placeholder' => '1234abcd',
        '#default_value' => $this->config('recaptcha.settings')->get('secret_key'),
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
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#value'] == 'Save') {
      $recaptcha_site_key = $form_state->getValue(['site_key']);
      $recaptcha_secret_key = $form_state->getValue(['secret_key']);
      if (empty($recaptcha_site_key)) {
        $form_state->setErrorByName('site_key', $this->t('Site key is required.'));
      }
      if (empty($recaptcha_secret_key)) {
        $form_state->setErrorByName('secret_key', $this->t('Secret key is required.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function saveConfig(array &$form, FormStateInterface $form_state) {
    $recaptcha_site_key = $form_state->getValue(['site_key']);
    $recaptcha_secret_key = $form_state->getValue(['secret_key']);
    $this->config('recaptcha.settings')->set('site_key', $recaptcha_site_key)->save();
    $this->config('recaptcha.settings')->set('secret_key', $recaptcha_secret_key)->save();
    $this->setConfigurationState();
    $this->messenger()->addStatus('The configuration options have been saved.');
  }

  /**
   * {@inheritdoc}
   */
  public function ignoreConfig(array &$form, FormStateInterface $form_state) {
    $this->setConfigurationState();
  }

}
