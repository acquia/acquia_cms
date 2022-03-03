<?php

namespace Drupal\acquia_cms_site_studio\Plugin\AcquiaCmsTour;

use Drupal\acquia_cms_tour\AcquiaCmsTourPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the acquia_cms_tour.
 *
 * @AcquiaCmsTour(
 *   id = "site_studio",
 *   label = @Translation("Site studio")
 * )
 */
class SiteStudioCoreForm extends AcquiaCmsTourPluginBase {

  /**
   * @inheridoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = FALSE;
    $form['api_key'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('API key'),
      '#placeholder' => '1234abcd',
      '#default_value' => $this->config('cohesion.settings')->get('api_key'),
      '#prefix' => '<div class= "dashboard-fields-wrapper">',
    ];
    $form['agency_key'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Agency key'),
      '#placeholder' => '1234abcd',
      '#default_value' => $this->config('cohesion.settings')->get('organization_key'),
      '#suffix' => "</div>",
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
      '#button_type' => 'primary',
      '#prefix' => '<div class= "dashboard-buttons-wrapper">',
    ];
    $form['actions']['ignore'] = [
      '#type' => 'submit',
      '#value' => 'Ignore',
      '#limit_validation_errors' => [],
      '#submit' => ['::ignoreConfig'],
    ];
    $form['actions']['advanced'] = [
      '#prefix' => '<div class= "dashboard-tooltiptext">',
      '#suffix' => "</div>",
    ];
    $form['actions']['advanced']['information'] = [
      '#prefix' => '<b class= "tool-tip__icon">i',
      '#suffix' => "</b>",
    ];
    $form['actions']['advanced']['tooltip-text'] = [
      '#prefix' => '<span class= "tooltip">',
      '#markup' => $this->t("Opens Advance Configuration in new tab"),
      '#suffix' => "</span></div>",
    ];
    return $form;
  }

  /**
   * @inheridoc
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement validateConfigurationForm() method.
  }

  /**
   * @inheridoc
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitConfigurationForm() method.
  }

}
