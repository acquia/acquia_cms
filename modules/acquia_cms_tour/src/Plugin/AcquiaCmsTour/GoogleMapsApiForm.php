<?php

namespace Drupal\acquia_cms_tour\Plugin\AcquiaCmsTour;

use Drupal\acquia_cms_tour\Form\AcquiaCMSDashboardBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\geocoder\GeocoderProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the acquia_cms_tour.
 *
 * @AcquiaCmsTour(
 *   id = "geocoder",
 *   label = @Translation("Geocoder"),
 *   weight = 2
 * )
 */
class GoogleMapsApiForm extends AcquiaCMSDashboardBase {

  /**
   * Provides module name.
   *
   * @var string
   */
  protected $module = 'geocoder';

  /**
   * The Geocoder provider entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $geocoderProviderStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var static $instance */
    $instance = parent::create($container);

    $entity_type_manager = $container->get('entity_type.manager');
    if ($entity_type_manager->hasDefinition('geocoder_provider')) {
      $instance->geocoderProviderStorage = $entity_type_manager->getStorage('geocoder_provider');
    }
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_maps_api_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cohesion.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Text input for Google Maps. ACMS can use the Gmaps API in two totally
    // different features (Site Studio and Place nodes). Site Studio is always
    // enabled in ACMS, but Place may not.
    $module = $this->module;
    if ($this->isModuleEnabled()) {
      $module_path = $this->moduleHandler->getModule($module)->getPathname();
      $module_info = $this->infoParser->parse($module_path);
      $maps_api_key = $this->config('cohesion.settings')
        ->get('google_map_api_key');
      $provider = $this->loadProvider();
      if ($provider) {
        $configuration = $provider->get('configuration');
        $maps_api_key = $configuration['apiKey'];
      }

      $configured = $this->getConfigurationState();

      if ($configured) {
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
      $form[$module]['acquia_google_maps_api'] = [
        'maps_api_key' => [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#title' => $this->t('Maps API key'),
          '#placeholder' => 'xxx-xxx-xxx',
          '#description' => $this->t('Enter your Google Maps API Key to automatically generate maps for Place content in Acquia CMS.'),
          '#default_value' => $maps_api_key,
          '#prefix' => '<div class= "dashboard-fields-wrapper">' . $module_info['description'],
          '#suffix' => "</div>",
        ],
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
      $form[$module]['actions']['advanced'] = [
        '#prefix' => '<div class= "dashboard-tooltiptext">',
        '#markup' => $this->linkGenerator->generate(
          'Advanced',
          Url::fromRoute('entity.geocoder_provider.collection')
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

      return $form;
    }
  }

  /**
   * Loads the Geocoder provider for Google Maps, if it exists.
   *
   * @return \Drupal\geocoder\GeocoderProviderInterface|null
   *   The Geocoder provider entity, or NULL if it does not exist.
   */
  private function loadProvider() : ?GeocoderProviderInterface {
    if ($this->geocoderProviderStorage) {
      return $this->geocoderProviderStorage->load('googlemaps');
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $maps_api_key = $form_state->getValue('maps_api_key');

    // Configure Google Maps API Key for both Site Studio and
    // Geocoder module.
    $this->config('cohesion.settings')
      ->set('google_map_api_key', $maps_api_key)
      ->save(TRUE);

    $provider = $this->loadProvider();
    if ($provider) {
      $configuration = $provider->get('configuration');
      $configuration['apiKey'] = $maps_api_key;
      $provider->set('configuration', $configuration);
      $this->geocoderProviderStorage->save($provider);
    }

    $this->setConfigurationState();

    $this->messenger()->addStatus('The Google Maps API key has been set.');
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
    $maps_api_key = $this->config('cohesion.settings')->get('google_map_api_key');
    $provider = $this->loadProvider();
    if ($provider) {
      $configuration = $provider->get('configuration');
      $maps_api_key = $configuration['apiKey'];
    }
    return !empty($maps_api_key);
  }

}
