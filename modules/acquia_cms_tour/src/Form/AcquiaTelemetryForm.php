<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to toggle the Acquia Telemetry module.
 */
final class AcquiaTelemetryForm extends FormBase {

  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  private $moduleInstaller;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * AcquiaTelemetryForm constructor.
   *
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The module installer.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleInstallerInterface $module_installer, ModuleHandlerInterface $module_handler) {
    $this->moduleInstaller = $module_installer;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_installer'),
      $container->get('module_handler')
    );
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Checkbox for Acquia Telemetry.
    $form['acquia_telemetry'] = [
      'opt_in' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Send anonymous data about Acquia product usage'),
        '#default_value' => $this->moduleHandler->moduleExists('acquia_telemetry'),
        '#description' => $this->t('This module intends to collect anonymous data about Acquia product usage. No private information will be gathered. Data will not be used for marketing or sold to any third party. This is an opt-in module and can be disabled at any time by uninstalling the acquia_telemetry module by your site administrator.'),
      ],
      '#type' => 'fieldset',
      '#open' => TRUE,
      '#title' => $this->t('Acquia Telemetry'),
    ];

    $form['acquia_telemetry']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
      '#button_type' => 'primary',
    ];

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
      $this->messenger()->addStatus('You have opted into Acquia Telemetry. Thank you for helping improve Acquia products.');
    }
    else {
      $this->moduleInstaller->uninstall(['acquia_telemetry']);
      $this->messenger()->addStatus('You have successfully opted out of Acquia Telemetry. Anonymous usage information will no longer be collected.');
    }
  }

}
