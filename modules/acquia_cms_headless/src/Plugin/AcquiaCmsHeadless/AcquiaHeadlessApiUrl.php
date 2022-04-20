<?php

namespace Drupal\acquia_cms_headless\Plugin\AcquiaCmsHeadless;

use Drupal\acquia_cms_tour\Form\AcquiaCMSDashboardBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the acquia_cms_headless.
 *
 * @AcquiaCmsHeadless(
 *   id = "headless_api_url",
 *   label = @Translation("Acquia CMS Headless API URL"),
 *   weight = 1
 * )
 */
class AcquiaHeadlessApiUrl extends AcquiaCMSDashboardBase {

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'jsonapi_extras';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_headless_api_url';
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
    $header = [];
    $rows = [];
    $part = 'jsonapi';
    $base = Url::fromRoute('<front>')->setAbsolute(TRUE)->toString();
    $url = Url::fromUri($base . $part)->toString();

    // Add prefix and suffix markup to implement a column layout.
    $form['#prefix'] = '<div class="layout-column layout-column--half">';
    $form['#suffix'] = '</div>';

    $form[$module] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API URL'),
    ];
    $form[$module]['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    $form[$module]['links'] = [
      '#type' => 'link',
      '#title' => $url,
      '#url' => Url::fromUri('internal:/jsonapi'),
      '#attributes' => ['target' => '_blank'],
    ];

    $form[$module]['text']['#markup'] = '<br />The base API Url.<br />';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Set the config state.
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
  public function checkMinConfiguration(): bool {
    return TRUE;
  }

}
