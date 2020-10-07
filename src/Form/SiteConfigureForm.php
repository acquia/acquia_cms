<?php

namespace Drupal\acquia_cms\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Installer\Form\SiteConfigureForm as CoreSiteConfigureForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends the installer's site configuration form to configure Cohesion.
 */
final class SiteConfigureForm extends ConfigFormBase {

  /**
   * The Cohesion API URL.
   *
   * @var string
   */
  private $apiUrl;

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
   * The decorated form object.
   *
   * @var \Drupal\Core\Installer\Form\SiteConfigureForm
   */
  private $decorated;

  /**
   * SiteConfigureForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param string $api_url
   *   The Cohesion API URL.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The module installer.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Installer\Form\SiteConfigureForm $decorated
   *   The decorated form object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    string $api_url,
    ModuleInstallerInterface $module_installer,
    ModuleHandlerInterface $module_handler,
    CoreSiteConfigureForm $decorated
  ) {
    parent::__construct($config_factory);
    $this->apiUrl = $api_url;
    $this->moduleInstaller = $module_installer;
    $this->moduleHandler = $module_handler;
    $this->decorated = $decorated;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cohesion.api.utils')->getAPIServerURL(),
      $container->get('module_installer'),
      $container->get('module_handler'),
      CoreSiteConfigureForm::create($container)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->decorated->getFormId();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = $this->decorated->buildForm($form, $form_state);

    $form['cohesion'] = [
      'api_key' => [
        '#type' => 'textfield',
        '#title' => $this->t('API key'),
        '#default_value' => getenv('COHESION_API_KEY'),
      ],
      'organization_key' => [
        '#type' => 'textfield',
        '#title' => $this->t('Organization key'),
        '#default_value' => getenv('COHESION_ORG_KEY'),
      ],
      '#type' => 'details',
      '#title' => $this->t('Acquia Site Studio'),
      '#description' => $this->t('Enter your API key and organization key to automatically set up Acquia Site Studio (note that this process can take a while). If you do not want to use Site Studio right now, leave these fields blank -- you can always set it up later.'),
      '#tree' => TRUE,
    ];
    $form['acquia_google_maps_api'] = [
      'maps_api_key' => [
        '#type' => 'textfield',
        '#title' => $this->t('Maps API key'),
        '#default_value' => '',
      ],
      '#type' => 'details',
      '#title' => $this->t('Google Maps'),
      '#description' => $this->t('Enter your Google Maps APIkey.'),
      '#tree' => TRUE,
    ];
    // Checkbox for Acquia Telemetry.
    $form['acquia_telemetry'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send anonymous usage information to Acquia'),
      '#default_value' => 1,
      '#description' => $this->t('This module intends to collect anonymous data about Acquia product usage. No private information will be gathered. Data will not be used for marketing or sold to any third party. This is an opt-in module and can be disabled at any time by uninstalling the acquia_telemetry module by your site administrator.'),
    ];
    $form['decoupled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable decoupled functionality'),
      '#description' => $this->t('If checked, additional modules will be installed to help you build your site as a content backend for mobile apps.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cohesion.settings',
      'geocoder.geocoder_provider.googlemaps',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->decorated->submitForm($form, $form_state);

    $api_key = $form_state->getValue(['cohesion', 'api_key']);
    $org_key = $form_state->getValue(['cohesion', 'organization_key']);
    $maps_api_key = $form_state->getValue([
      'acquia_google_maps_api',
      'maps_api_key',
    ]);

    if ($api_key && $org_key) {
      // For reasons I can't fathom, not resetting the config factory causes
      // a non-interactive install (i.e., drush site:install) to be unable to
      // load the API and organization keys in acquia_cms_install_tasks(), which
      // in turn results in Cohesion's stuff not getting imported. So, although
      // this may look bizarre, leave it as-is.
      $this->resetConfigFactory();

      $this->config('cohesion.settings')
        ->set('api_url', $this->apiUrl)
        ->set('api_key', $api_key)
        ->set('organization_key', $org_key)
        ->save(TRUE);
    }
    // Enable the Acquia Telemetry module if user opt's in.
    $acquia_telemetry_opt_in = $form_state->getValue('acquia_telemetry');
    if ($acquia_telemetry_opt_in) {
      $this->moduleInstaller->install(['acquia_telemetry']);
    }
    // Enable the JSON API Extras if user opts in for decoupled functionality.
    if ($form_state->getValue('decoupled')) {
      $this->moduleInstaller->install(['jsonapi_extras']);
    }
    // Configure Google Maps API Key.
    if ($maps_api_key) {

      // Site Studio is always on, so this is essentially safe to set.
      $this->config('cohesion.settings')
        ->set('google_map_api_key', $maps_api_key)
        ->save(TRUE);

      // ACMS Place may not be installed, so test if it's on before setting the
      // key here.
      if ($this->moduleHandler->moduleExists('acquia_cms_place')) {
        $this->config('geocoder.geocoder_provider.googlemaps')
          ->set('apiKey', $maps_api_key)
          ->save(TRUE);
      }
    }
  }

}
