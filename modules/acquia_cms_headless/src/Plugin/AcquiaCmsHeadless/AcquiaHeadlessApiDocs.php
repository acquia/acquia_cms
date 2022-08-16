<?php

namespace Drupal\acquia_cms_headless\Plugin\AcquiaCmsHeadless;

use Drupal\acquia_cms_tour\Form\AcquiaCmsDashboardBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the acquia_cms_headless.
 *
 * @AcquiaCmsHeadless(
 *   id = "headless_api_docs",
 *   label = @Translation("Acquia CMS Headless API Documentation"),
 *   weight = 2
 * )
 */
class AcquiaHeadlessApiDocs extends AcquiaCmsDashboardBase {

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'openapi_ui_redoc';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_headless_api_docs';
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
    $site_path = Url::fromRoute('<front>')->setAbsolute(TRUE)->toString();
    $headless_path = $this->moduleHandler->getModule('acquia_cms_headless')->getPath();
    $open_api_image = $site_path . $headless_path . '/assets/images/OpenAPI_Logo_Pantone-1.png';

    // Add prefix and suffix markup to implement a column layout.
    $form['#prefix'] = '<div class="layout-column layout-column--half">';
    $form['#suffix'] = '</div>';

    $form[$module] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API Documentation'),
    ];

    $form[$module]['openapi'] = [
      '#type' => 'link',
      '#title' => $this->t('<img width="300" height="91" alt="OpenAPI Initiative" src="@image">', ['@image' => $open_api_image]),
      '#url' => Url::fromUri('https://www.openapis.org/', ['external' => TRUE]),
      '#attributes' => [
        'target' => '_blank',
        'class' => [],
      ],
      '#prefix' => '<div class="headless-dashboard-openapi-logo">',
      '#suffix' => '</div>',
    ];

    $form[$module]['redoc'] = [
      '#type' => 'link',
      '#title' => 'Explore with Redoc',
      '#url' => Url::fromUri('internal:/admin/config/services/openapi/redoc/jsonapi'),
      '#attributes' => [
        'target' => '_blank',
        'class' => [
          'button',
          'button--primary',
        ],
      ],
      '#prefix' => '<div class="headless-dashboard-openapi-links">',
    ];

    $form[$module]['swagger'] = [
      '#type' => 'link',
      '#title' => 'Explore with Swagger UI',
      '#url' => Url::fromUri('internal:/admin/config/services/openapi/swagger/jsonapi'),
      '#attributes' => [
        'target' => '_blank',
        'class' => [
          'button',
          'button--primary',
        ],
      ],
      '#suffix' => '</div>',
    ];

    $form[$module]['resources'] = [
      '#type' => 'link',
      '#title' => 'OpenAPI Resources',
      '#url' => Url::fromUri('internal:/admin/config/services/openapi'),
      '#attributes' => [
        'target' => '_blank',
        'class' => [],
      ],
      '#prefix' => '<div class="headless-dashboard-openapi-resources"><p>',
      '#suffix' => '</p></div>',
    ];

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
