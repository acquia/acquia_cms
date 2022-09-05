<?php

namespace Drupal\acquia_cms_headless\Plugin\AcquiaCmsTour;

use Drupal\acquia_cms_tour\Form\AcquiaCmsDashboardBase;
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
class AcquiaHeadlessForm extends AcquiaCmsDashboardBase {

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
   * Provides Next.js starter kit Service.
   *
   * @var \Drupal\acquia_cms_headless\Service\StarterkitNextjsService
   */
  protected $starterkitNextjsService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->moduleInstaller = $container->get('module_installer');
    $instance->starterkitNextjsService = $container->get('acquia_cms_headless.starterkit_nextjs');

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

      // Get nextjs consumer data.
      $is_next_js = $this->starterkitNextjsService->hasConsumerData();

      $form[$module] = [
        '#type' => 'details',
        '#title' => $this->t('Headless'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];
      $form[$module]['starterkit_nextjs'] = [
        '#type' => 'checkbox',
        '#required' => FALSE,
        '#title' => $this->t('Enable Next.js starter kit'),
        // @todo Update description of Next.js starter kit enabling.
        '#description' => $this->t('When the Next.js starter kit option is enabled,
          dependencies related to the Next.js module will be enabled and a default
          configuration will be initialized to ready Drupal to be connected with
          a next.js application.'),
        '#disabled' => $is_next_js,
        '#default_value' => $is_next_js,
        '#prefix' => '<div class= "dashboard-fields-wrapper">' . $module_info['description'],
      ];
      $form[$module]['headless_mode'] = [
        '#type' => 'checkbox',
        '#required' => FALSE,
        '#title' => $this->t('Enable Headless mode'),
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
      if ($config->get('starterkit_nextjs')) {
        $form[$module]['actions']['advanced'] = [
          '#prefix' => '<div class= "dashboard-tooltiptext">',
          '#markup' => $this->linkGenerator->generate(
            'Advanced',
            Url::fromRoute('acquia_cms_headless.dashboard')
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

      $css = '.messages pre.codesnippet { border: 1px solid white;
        border-radius: 0.5em;
        padding: 1em;
        margin: 1em 0;
        background-color: #333;';
      $form['#attached']['html_head'][] = [
        [
          '#tag' => 'style',
          '#value' => $css,
        ],
        'code-css',
      ];

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
    $config_starterkit_nextjs = $config->get('starterkit_nextjs');
    $config_headless = $config->get('headless_mode');

    // Get form state values.
    $acms_starterkit_nextjs = $form_state->getValue(['starterkit_nextjs']);
    $acms_headless_mode = $form_state->getValue(['headless_mode']);

    // Check to see on submit, if this is actually changing.  If yes, then we
    // either need to enable or disable modules related to starterkit nextjs.
    if ($config_starterkit_nextjs != $acms_starterkit_nextjs) {
      if ($acms_starterkit_nextjs) {
        try {
          $site_data = [
            'site-name' => 'Headless Site 1',
            'site-url' => 'http://localhost:3000',
          ];
          // Run the Next.js starter kit Initialization service.
          $this->starterkitNextjsService->initStarterkitNextjs('headless', $site_data);

          // Return a message to the user that the set has completed.
          $this->messenger()->addStatus($this->t('Acquia CMS Next.js starter kit has been enabled.'));
          $this->config('acquia_cms_headless.settings')->set('starterkit_nextjs', $acms_starterkit_nextjs)->save();
        }
        catch (InvalidPluginDefinitionException | PluginNotFoundException | EntityStorageException | ExtensionNotLoadedException | FilesystemValidationException $e) {
          $this->messenger()->addError($e->getMessage());
        }
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
    $starterkit_nextjs = (bool) $this->config('acquia_cms_headless.settings')->get('starterkit_nextjs');
    $headless_mode = (bool) $this->config('acquia_cms_headless.settings')->get('headless_mode');
    return $starterkit_nextjs && $headless_mode;
  }

}
