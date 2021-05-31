<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form to configure the Google Analytics module.
 */
final class GoogleAnalyticsForm extends AcquiaCMSDashboardBase {

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'google_analytics';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_google_analytics_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'google_analytics.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = FALSE;
    $module = $this->module;
    if ($this->isModuleEnabled()) {
      $configured = $this->getConfigurationState();
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

      $form[$module]['web_property_id'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Web Property ID'),
        '#placeholder' => 'UA-',
        '#default_value' => $this->config('google_analytics.settings')->get('account'),
        '#prefix' => '<div class= "dashboard-fields-wrapper">' . $module_info['description'],
        '#suffix' => "</div>",
      ];
      $form[$module]['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => 'Save',
        '#prefix' => '<div class= "dashboard-buttons-wrapper">',
      ];
      $form[$module]['actions']['ignore'] = [
        '#type' => 'submit',
        '#value' => 'Ignore',
        '#limit_validation_errors' => [],
        '#submit' => ['::ignoreConfig'],
      ];
      if (isset($module_info['configure'])) {
        $form[$module]['actions']['advanced'] = [
          '#prefix' => '<div class= "dashboard-tooltiptext">',
          '#markup' => $this->linkGenerator->generate(
            'Advanced',
            Url::fromRoute($module_info['configure'])
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
      }
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $property_id = $form_state->getValue(['web_property_id']);
    $this->config('google_analytics.settings')->set('account', $property_id)->save();
    $this->state->set('google_analytics_progress', TRUE);
    $this->messenger()->addStatus('The configuration options have been saved.');

    // Update state.
    $this->setConfigurationState();
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
    $account = $this->config('google_analytics.settings')->get('account');
    return $account ? TRUE : FALSE;
  }

}
