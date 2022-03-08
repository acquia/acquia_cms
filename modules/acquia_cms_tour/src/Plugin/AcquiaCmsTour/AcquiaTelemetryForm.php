<?php

namespace Drupal\acquia_cms_tour\Plugin\AcquiaCmsTour;

use Drupal\acquia_cms_tour\Form\AcquiaCMSDashboardBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the acquia_cms_tour.
 *
 * @AcquiaCmsTour(
 *   id = "acquia_telemetry",
 *   label = @Translation("Acquia Telemetry"),
 *   weight = 1
 * )
 */
class AcquiaTelemetryForm extends AcquiaCMSDashboardBase {

  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  private $moduleInstaller;

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'acquia_telemetry';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->moduleInstaller = $container->get('module_installer');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_cms_telemetry_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'acquia_telemetry.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = FALSE;
    $module = $this->module;
    $module_path = $this->moduleHandler->getModule($module)->getPathname();
    $module_info = $this->infoParser->parse($module_path);
    if ($this->getConfigurationState()) {
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
    $form[$module]['opt_in'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send anonymous data about Acquia product usage'),
      '#default_value' => $this->isModuleEnabled() ? 1 : 0,
      '#description' => $this->t('This module intends to collect anonymous data about Acquia product usage. No private information will be gathered. Data will not be used for marketing or sold to any third party. This is an opt-in module and can be disabled at any time by uninstalling the acquia_telemetry module by your site administrator.'),
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

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Enable the Acquia Telemetry module if user opts in.
    $acquia_telemetry_opt_in = $form_state->getValue('opt_in');

    if ($acquia_telemetry_opt_in) {
      $this->moduleInstaller->install(['acquia_telemetry']);
      $this->setConfigurationState();
      $this->messenger()->addStatus('You have opted into Acquia Telemetry. Thank you for helping improve Acquia products.');
    }
    else {
      $this->moduleInstaller->uninstall(['acquia_telemetry']);
      $this->setConfigurationState(FALSE);
      $this->messenger()->addStatus('You have successfully opted out of Acquia Telemetry. Anonymous usage information will no longer be collected.');
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
  public function checkMinConfiguration() {
    return $this->isModuleEnabled();
  }

}
