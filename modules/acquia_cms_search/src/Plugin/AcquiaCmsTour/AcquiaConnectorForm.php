<?php

namespace Drupal\acquia_cms_search\Plugin\AcquiaCmsTour;

use Drupal\acquia_cms_tour\Form\AcquiaCMSDashboardBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the acquia_cms_tour.
 *
 * @AcquiaCmsTour(
 *   id = "acquia_connector",
 *   label = @Translation("Acquia Connector"),
 *   weight = 7
 * )
 */
class AcquiaConnectorForm extends AcquiaCMSDashboardBase {

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
    if ($this->isModuleEnabled()) {
      $module_path = $this->moduleHandler->getModule($module)->getPathname();
      $module_info = $this->infoParser->parse($module_path);
      $form[$module] = [
        '#type' => 'details',
        '#title' => $module_info['name'],
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];
      $form[$module]['site_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#maxlength' => 255,
        '#disabled' => TRUE,
        '#required' => TRUE,
        '#default_value' => $this->state->get('spi.site_name'),
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

      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $acquia_connector_site_name = $form_state->getValue(['site_name']);
    $this->state->set('spi.site_name', $acquia_connector_site_name);
    // Set configuration state for dashboard.
    $this->setConfigurationState();
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
    $site_name = $this->state->get('spi.site_name');
    return (bool) $site_name;
  }

}
