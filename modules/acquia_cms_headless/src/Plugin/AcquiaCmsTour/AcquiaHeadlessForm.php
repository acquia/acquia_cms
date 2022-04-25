<?php

namespace Drupal\acquia_cms_headless\Plugin\AcquiaCmsTour;

use Drupal\acquia_cms_tour\Form\AcquiaCMSDashboardBase;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Extension\ExtensionNameLengthException;
use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simple_oauth\Service\Exception\ExtensionNotLoadedException;
use Drupal\simple_oauth\Service\Exception\FilesystemValidationException;
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
   * Provides Robust API Service.
   *
   * @var \Drupal\acquia_cms_headless\Service\RobustApiService
   */
  protected $robustApiService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->moduleInstaller = $container->get('module_installer');
    $instance->robustApiService = $container->get('acquia_cms_headless.robustapi');

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
    $headless = 'acquia_cms_headless_ui';

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
        // @todo Update description of Robust API enabling.
        '#description' => $this->t('When the Robust API option is enabled,
          dependencies related to the Next.js module will be enabled providing
          users with the ability to use Drupal as a backend for a decoupled
          NodeJS app while also retaining Drupal’s default front-end.
          E.g., with a custom theme.'),
        '#default_value' => (bool) $config->get('robust_api'),
        '#prefix' => '<div class= "dashboard-fields-wrapper">' . $module_info['description'],
      ];
      $form[$module]['headless_mode'] = [
        '#type' => 'checkbox',
        '#required' => FALSE,
        '#title' => $this->t('Enable Headless mode'),
        // @todo Update description of Pure Headless enabling.
        '#description' => $this->t('When Headless Mode is enabled, it
          turns on all the capabilities that allows Drupal to be used as a
          backend for a decoupled Node JS app AND turns off all of Drupal’s
          front-end features so that the application is<em>purelyheadless</em>.'),
        '#default_value' => $this->moduleHandler->moduleExists($headless),
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
    // Get existing Acquia CMS Headless config settings.
    $config = $this->config('acquia_cms_headless.settings');

    // Get current form values so that we have something to compare against.
    $config_robustapi = $config->get('robust_api');
    $config_headless = $config->get('headless_mode');

    // Get form state values.
    $acms_robustapi = $form_state->getValue(['robust_api']);
    $acms_headless_mode = $form_state->getValue(['headless_mode']);

    // Check to see on submit, if this is actually changing.  If yes, then we
    // either need to enable or disable modules related to robust api.
    if ($config_robustapi != $acms_robustapi) {
      if ($acms_robustapi) {
        try {
          // Run the Robust API Initialization service.
          $this->robustApiService->initRobustApi();
          // Return a message to the user that the set has completed.
          $this->messenger()->addStatus($this->t('Acquia CMS Robust API has been enabled.'));
        }
        catch (InvalidPluginDefinitionException | PluginNotFoundException | EntityStorageException | ExtensionNotLoadedException | FilesystemValidationException $e) {
          $this->messenger()->addError($e);
        }
      }
      else {
        $this->messenger()->addStatus($this->t('Acquia CMS Robust API has been disabled.'));
      }
    }

    // Check to see on submit, if this is actually changing.  If yes, then we
    // either need to enable or disable modules related to pure headless mode.
    if ($config_headless != $acms_headless_mode) {
      if ($acms_headless_mode) {
        try {
          // Install the Acquia CMS Pure headless module.
          $this->moduleInstaller->install(['acquia_cms_headless_ui']);
          $this->messenger()->addStatus($this->t('Acquia CMS Pure Headless has been enabled.'));
        }
        catch (ExtensionNameLengthException | MissingDependencyException $e) {
          $this->messenger()->addError($e);
        }
      }
      else {
        $this->moduleInstaller->uninstall(['acquia_cms_headless_ui']);
        $this->messenger()->addStatus($this->t('Acquia CMS Pure Headless has been disabled.'));
      }
    }

    // Proceed with form save and configuration settings actions.
    // Set and save the form values.
    $this->config('acquia_cms_headless.settings')->set('robust_api', $acms_robustapi)->save();
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
