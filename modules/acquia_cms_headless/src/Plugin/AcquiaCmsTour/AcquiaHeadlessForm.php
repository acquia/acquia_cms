<?php

namespace Drupal\acquia_cms_headless\Plugin\AcquiaCmsTour;

use Drupal\acquia_cms_tour\Form\AcquiaCMSDashboardBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the acquia_cms_tour.
 *
 * @AcquiaCmsTour(
 *   id = "acquia_cms_headless",
 *   label = @Translation("Acquia CMS Headless"),
 *   weight = 8
 * )
 */
class AcquiaHeadlessForm extends AcquiaCMSDashboardBase {

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'acquia_cms_headless';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_headless_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['acquia_cms_headless.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = FALSE;
    $module = $this->module;

    if ($this->isModuleEnabled()) {
      $config = $this->config('acquia_cms_headless.settings');
      dpm($config);
      $configured = $this->getConfigurationState();

      if ($configured) {
        $form['check_icon'] = [
          '#prefix' => '<span class= "dashboard-check-icon">',
          '#suffix' => "</span>",
        ];
      }
      $form[$module] = [
        '#type' => 'details',
        '#title' => $this->t('Headless'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];
      $form[$module]['robust_api'] = [
        '#type' => 'checkbox',
        '#required' => FALSE,
        '#title' => $this->t('Enable robust API capabilities (Next.js Drupal)'),
        '#description' => $this->t('@todo Input description of what happens when Robust API is enabled.'),
        '#default_value' => $config->get('robust_api') ? $config->get('robust_api') : 0,
        '#prefix' => '<div class= "dashboard-fields-wrapper">' . $this->t('@todo Provide better description of these options, etc.'),
      ];
      $form[$module]['headless_mode'] = [
        '#type' => 'checkbox',
        '#required' => FALSE,
        '#title' => $this->t('Enable "Pure Headless" mode'),
        '#description' => $this->t('@todo Input description of what happens when Pure Headless mode is enabled.'),
        '#default_value' => $config->get('headless_mode') ? $config->get('headless_mode') : 0,
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
      if (isset($module_info['configure'])) {
        // @todo Link to API dashboard. Will be added via AMCS-1083.
        $form[$module]['actions']['advanced'] = [
          '#prefix' => '<div class= "dashboard-tooltiptext">',
          '#markup' => $this->linkGenerator->generate(
            'Advanced',
            Url::fromRoute('cohesion.configuration.account_settings')
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
    // Get form state values.
    $acms_robust_api = $form_state->getValue(['robust_api']);
    $acms_headless_mode = $form_state->getValue(['headless_mode']);

    // Set and save the form values.
    $this->config('acquia_cms_headless.settings')->set('robust_api', $acms_robust_api)->save();
    $this->config('acquia_cms_headless.settings')->set('headless_mode', $acms_headless_mode)->save();

    // Set the config state.
    $this->setConfigurationState();
    // Add status message for user.
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
  public function checkMinConfiguration(): bool {
    $robust_api = (bool) $this->config('acquia_cms_headless.settings')->get('robust_api');
    $headless_mode = (bool) $this->config('acquia_cms_headless.settings')->get('headless_mode');
    return $robust_api && $headless_mode;
  }

}
