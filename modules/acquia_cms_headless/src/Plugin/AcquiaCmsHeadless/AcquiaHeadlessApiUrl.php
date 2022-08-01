<?php

namespace Drupal\acquia_cms_headless\Plugin\AcquiaCmsHeadless;

use Drupal\acquia_cms_tour\Form\AcquiaCMSDashboardBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Provides Starter Kit Next.js Service.
   *
   * @var \Drupal\acquia_cms_headless\Service\StarterkitNextjsService
   */
  protected $starterKitNextjsService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->starterKitNextjsService = $container->get('acquia_cms_headless.starterkit_nextjs');

    return $instance;
  }

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

    // Get the JSON API URI.
    $json_preview_url = Url::fromUri('internal:/jsonapi');
    $site_path = Url::fromRoute('<front>')->setAbsolute(TRUE)->toString();
    $headless_path = $this->moduleHandler->getModule('acquia_cms_headless')->getPath();
    $jsonapi_image = $site_path . $headless_path . '/assets/images/json-api.png';

    // Set the destination query array.
    $destination = $this->starterKitNextjsService->dashboardDestination();

    // Add prefix and suffix markup to implement a column layout.
    $form['#prefix'] = '<div class="layout-column layout-column--half">';
    $form['#suffix'] = '</div>';

    $form[$module] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API URL'),
    ];

    $form[$module]['jsonapi'] = [
      '#type' => 'link',
      '#title' => $this->t('<img alt="json:api Initiative" src="@image">', ['@image' => $jsonapi_image]),
      '#url' => Url::fromUri('https://jsonapi.org/', ['external' => TRUE]),
      '#attributes' => [
        'target' => '_blank',
        'class' => [],
      ],
      '#prefix' => '<div class="headless-dashboard-openapi-logo">',
      '#suffix' => '</div>',
    ];

    $form[$module]['api_link'] = [
      '#type' => 'link',
      '#title' => $json_preview_url->setAbsolute(TRUE)->toString(),
      '#url' => $json_preview_url,
      '#attributes' => [
        'target' => '_blank',
      ],
      '#prefix' => '<div class="headless-dashboard-api-url"><p><strong>Base API Url: </strong><span>',
      '#suffix' => '</span></p></div>',
    ];

    $form[$module]['update_api'] = [
      '#type' => 'link',
      '#title' => 'Update Base API URL',
      '#url' => Url::fromRoute('jsonapi_extras.settings', [], $destination),
      '#attributes' => [
        'class' => [
          'button',
          'button--primary',
        ],
      ],
      '#prefix' => '<div class="headless-dashboard-api-update">',
      '#suffix' => '</div>',
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
