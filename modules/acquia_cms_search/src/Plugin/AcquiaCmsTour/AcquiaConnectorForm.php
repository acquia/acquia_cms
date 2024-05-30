<?php

namespace Drupal\acquia_cms_search\Plugin\AcquiaCmsTour;

use Drupal\acquia_cms_tour\Form\AcquiaCmsDashboardBase;
use Drupal\acquia_connector\SiteProfile\SiteProfile;
use Drupal\acquia_connector\Subscription;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the acquia_cms_tour.
 *
 * @AcquiaCmsTour(
 *   id = "acquia_connector",
 *   label = @Translation("Acquia Connector"),
 *   weight = 7
 * )
 */
class AcquiaConnectorForm extends AcquiaCmsDashboardBase {

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'acquia_connector';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_connector_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['acquia_connector.settings'];
  }

  /**
   * Acquia Connector Subscription service.
   *
   * @var \Drupal\acquia_connector\Subscription
   */
  protected Subscription $subscription;

  /**
   * The site profile.
   *
   * @var \Drupal\acquia_connector\SiteProfile\SiteProfile
   */
  protected SiteProfile $siteProfile;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    if ($container->get('module_handler')->moduleExists('acquia_connector')) {
      $instance->siteProfile = $container->get('acquia_connector.site_profile');
      $instance->subscription = $container->get('acquia_connector.subscription');
    }

    return $instance;
  }

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
    // Check acquia_connector module is enabled.
    if ($this->isModuleEnabled()) {
      $module_path = $this->moduleHandler->getModule($module)->getPathname();
      $module_info = $this->infoParser->parse($module_path);
      $form[$module] = [
        '#type' => 'details',
        '#title' => $module_info['name'],
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];
      // Start with an empty subscription.
      $subscription = $this->subscription->getSubscription(TRUE);
      $site_name = isset($subscription['uuid']) ? $this->siteProfile->getSiteName($subscription['uuid']) : "";
      $form[$module]['site_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#maxlength' => 255,
        '#disabled' => TRUE,
        '#required' => TRUE,
        '#default_value' => $this->state->get('spi.site_name') ?? $site_name,
        '#prefix' => '<div class= "dashboard-fields-wrapper">' . $module_info['description'],
        '#suffix' => '</div class= "dashboard-fields-wrapper">',
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
          '#suffix' => '</div>',
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
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $acquia_connector_site_name = $form_state->getValue(['site_name']);
    $this->state->set('spi.site_name', $acquia_connector_site_name);
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
    $site_name = $this->state->get('spi.site_name');
    return (bool) $site_name;
  }

}
