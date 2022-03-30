<?php

namespace Drupal\acquia_cms_headless\Plugin\AcquiaCmsTour;

use Drupal\acquia_cms_tour\Form\AcquiaCMSDashboardBase;
use Drupal\Core\Extension\ExtensionNameLengthException;
use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the acquia_cms_tour.
 *
 * @AcquiaCmsTour(
 *   id = "acquia_cms_headless",
 *   label = @Translation("Acquia CMS Headless"),
 *   weight = 8
 * )
 */
class AcquiaHeadlessForm extends AcquiaCMSDashboardBase {

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
  protected $module = 'acquia_cms_headless';

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
    return 'acquia_cms_headless_form';
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

    if ($this->isModuleEnabled()) {
      $config = $this->config('acquia_cms_headless.settings');
      $configured = $this->getConfigurationState();
      $module_path = $this->moduleHandler->getModule($module)->getPathname();
      $module_info = $this->infoParser->parse($module_path);

      if ($configured) {
        $form['check_icon'] = [
          '#prefix' => '<span class= "dashboard-check-icon">',
          '#suffix' => "</span>",
        ];
      }
      $form[$module] = [
        '#type' => 'details',
        '#title' => $this->t('Headless'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];
      $form[$module]['robust_api'] = [
        '#type' => 'checkbox',
        '#required' => FALSE,
        '#title' => $this->t('Enable Robust API capabilities'),
        '#description' => $this->t('When the Robust API option is enabled,
          the Next.js module, its dependencies, and related modules will be
          installed providing users with the ability to us Drupal as a backend
          for a decoupled Node JS app while also retaining Drupal’s default
          frontend. E.g., with a custom theme.'),
        '#default_value' => $config->get('robust_api') ? $config->get('robust_api') : 0,
        '#prefix' => '<div class= "dashboard-fields-wrapper">' . $module_info['description'],
      ];
      $form[$module]['headless_mode'] = [
        '#type' => 'checkbox',
        '#required' => FALSE,
        '#title' => $this->t('Enable Headless mode'),
        '#description' => $this->t('When Headless Mode is enabled, it
          turns on all the capabilities that allows Drupal to be used as a
          backend for a decoupled Node JS app AND turns off all of Drupal’s
          frontend features so that the application is
          <em>purely headless</em>.<br><br><strong>Warning</strong>: This will
          remove any data related to Site Studio, Layout Builder, etc. Proceed
          with caution and backup any necessary data prior to enabling.
          Additionally, when disabled, this will undo any elements added and
          Reinstalled UI.'),
        '#default_value' => $config->get('headless_mode') ? $config->get('headless_mode') : 0,
        '#suffix' => "</div>",
      ];
      $form[$module]['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => 'Save',
        '#button_type' => 'primary',
        '#prefix' => '<div class= "dashboard-buttons-wrapper">',
      ];
      $form[$module]['actions']['ignore'] = [
        '#type' => 'submit',
        '#value' => 'Ignore',
        '#limit_validation_errors' => [],
        '#submit' => ['::ignoreConfig'],
      ];
      if (isset($module_info['configure'])) {
        // @todo Link to API dashboard. Will be added via AMCS-1083.
        $form[$module]['actions']['advanced'] = [
          '#prefix' => '<div class= "dashboard-tooltiptext">',
          '#markup' => $this->linkGenerator->generate(
            'Advanced',
            Url::fromRoute('cohesion.configuration.account_settings')
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
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get form state values.
    $acms_robust_api = $form_state->getValue(['robust_api']);
    $acms_headless_mode = $form_state->getValue(['headless_mode']);

    // When Robust API is enabled, or disabled, we need to complete a few
    // actions on form submit.
    if ($acms_robust_api) {
      $success_message = $this->t('The configuration options have been saved.
        <ul>
          <li>The modules, <strong>Next.js</strong> and <strong>Next.js JSON:API</strong>, have been installed.</li>
        </ul>
      ');
      try {
        // Install the NextJS and NextJS JSON API modules.
        // @todo Add other "Robust" functions when enabled.
        $this->moduleInstaller->install(['next', 'next_jsonapi']);

        // Add status message for user.
        $this->messenger()->addStatus($success_message);
      }
      catch (ExtensionNameLengthException | MissingDependencyException $e) {
        $this->messenger()->addError($e->getMessage());
      }
    }
    else {
      $success_message = $this->t('The configuration options have been saved.
        <ul>
          <li>The modules, <strong>Next.js</strong> and <strong>Next.js JSON:API</strong>, have been uninstalled.</li>
        </ul>
      ');
      // Uninstall the NextJS and NextJS JSON API modules.
      // @todo Additional items that needs to happen when "Robust" is disabled.
      $this->moduleInstaller->uninstall(['next', 'next_jsonapi']);

      // Add status message for user.
      $this->messenger()->addStatus($success_message);
    }

    if ($acms_headless_mode) {
      // @todo Add "Headless" functions when enabled.
      // Add status message for user.
      $this->messenger()->addStatus($this->t('Acquia CMS Pure Headless mode has been enabled.'));
    }
    else {
      // @todo Remove "Headless" functions when disabled.
      $this->messenger()->addStatus($this->t('Acquia CMS Pure Headless mode has been disabled.'));
    }

    // Set and save the form values.
    $this->config('acquia_cms_headless.settings')->set('robust_api', $acms_robust_api)->save();
    $this->config('acquia_cms_headless.settings')->set('headless_mode', $acms_headless_mode)->save();

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
    $robust_api = (bool) $this->config('acquia_cms_headless.settings')->get('robust_api');
    $headless_mode = (bool) $this->config('acquia_cms_headless.settings')->get('headless_mode');
    return $robust_api && $headless_mode;
  }

}
