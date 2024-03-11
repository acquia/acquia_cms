<?php

namespace Drupal\acquia_cms_common\Plugin\AcquiaCmsTour;

use Drupal\acquia_cms_tour\Form\AcquiaCmsDashboardBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the acquia_cms_tour.
 *
 * @AcquiaCmsTour(
 *   id = "acquia_cms_telemetry",
 *   label = @Translation("Acquia Telemtry"),
 *   weight = 7
 * )
 */
class AcquiaCmsTelemetryForm extends AcquiaCmsDashboardBase {

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'acquia_cms_common';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_telemetry_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): void {}

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = FALSE;
    $module = $this->module;
    $configured = $this->getConfigurationState();
    if ($configured) {
      $form['check_icon'] = [
        '#prefix' => '<span class= "dashboard-check-icon">',
        '#suffix' => "</span>",
      ];
    }
    $form[$module] = [
      '#type' => 'details',
      '#title' => "Acquia Telemetry",
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form[$module]['opt_in'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send anonymous data about Acquia product usage'),
      '#default_value' => $this->state->get('acquia_connector.telemetry.opted'),
      '#description' => $this->t('In order to improve our services Acquia collects anonymous information about product usage and performance. The data will never be used for marketing or sold to third parties. Please uncheck this box if you do not wish for this data to be collected.'),
      '#prefix' => '<div class= "dashboard-fields-wrapper">',
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $acquia_telemetry_opt_in = $form_state->getValue('opt_in');
    if ($acquia_telemetry_opt_in) {
      $this->state->set('acquia_connector.telemetry.opted', TRUE);
      $this->setConfigurationState();
      $this->messenger()->addStatus('You have opted into collect anonymous data about Acquia product usage. Thank you for helping improve Acquia products.');
    }
    else {
      $this->state->set('acquia_connector.telemetry.opted', FALSE);
      $this->setConfigurationState(FALSE);
      $this->messenger()->addStatus('You have successfully opted out to collect anonymous data about Acquia product usage. Anonymous usage information will no longer be collected.');
    }
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
  public function checkMinConfiguration(): void {}

}
