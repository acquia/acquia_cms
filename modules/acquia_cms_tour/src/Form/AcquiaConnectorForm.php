<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form to configure Acquia Connector.
 */
final class AcquiaConnectorForm extends AcquiaCMSDashboardBase {

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
    $site_name = $this->state->get('spi.site_name');
    $configured = $this->getProgressState();
    if (!empty($site_name)) {
      $configured = TRUE;
      $this->setState();
    }
    if ($configured) {
      $form['check_icon'] = [
        '#prefix' => '<span class= "dashboard-check-icon">',
        '#suffix' => "</span>",
      ];
    }
    if ($this->module_handler->moduleExists($module)) {
      $module_path = $this->module_handler->getModule($module)->getPathname();
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
        '#default_value' => $this->state->get('spi.site_name'),
        '#prefix' => '<div class= "dashboard-fields-wrapper">' . $module_info['description'],
        '#suffix' => "</div>",
      ];

      $form[$module]['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => 'Save',
        '#submit' => ['::saveConfig'],
        '#prefix' => '<div class= "dashboard-buttons-wrapper">',
      ];
      $form[$module]['actions']['ignore'] = [
        '#type' => 'submit',
        '#value' => 'Ignore',
        '#submit' => ['::ignoreConfig'],
      ];
      if (isset($module_info['configure'])) {
        $form[$module]['actions']['advanced'] = [
          '#markup' => $this->linkGenerator->generate(
            'Advanced',
            Url::fromRoute($module_info['configure'])
          ),
          '#suffix' => "</div>",
        ];
      }

      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#value'] == 'Save') {
      if (empty($form_state->getValue('site_name'))) {
        $form_state->setErrorByName('site_name', $this->t('Site name is required.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function saveConfig(array &$form, FormStateInterface $form_state) {
    $acquia_connector_site_name = $form_state->getValue(['site_name']);
    $this->state->set('spi.site_name', $acquia_connector_site_name);
    $this->state->set('acquia_connector_progress', TRUE);
    $this->messenger()->addStatus('The configuration options have been saved.');
    // Set configuration state for dashboard.
    $this->setState();
  }

  /**
   * {@inheritdoc}
   */
  public function ignoreConfig(array &$form, FormStateInterface $form_state) {
    $this->setState();
  }

}
