<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\geocoder\GeocoderProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to configure the API key for Google Maps.
 */
final class AcquiaGoogleMapsApiDashboardForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;


  /**
   * The info file parser.
   *
   * @var \Drupal\Core\Extension\InfoParserInterface
   */
  protected $infoParser;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The Geocoder provider entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $geocoderProviderStorage;

  /**
   * AcquiaTelemetryForm constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info file parser.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator.
   */
  public function __construct(StateInterface $state, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, InfoParserInterface $info_parser, LinkGeneratorInterface $link_generator) {
    $this->state = $state;
    $this->entityTypeManager = $entity_type_manager;
    $this->module_handler = $module_handler;
    $this->infoParser = $info_parser;
    $this->linkGenerator = $link_generator;
    if ($entity_type_manager->hasDefinition('geocoder_provider')) {
      $this->geocoderProviderStorage = $entity_type_manager->getStorage('geocoder_provider');
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('info_parser'),
      $container->get('link_generator')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_google_maps_api_form';
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
    $module = 'geocoder';
    $state_var = $this->getProgressState();
    if (isset($state_var['count']) && $state_var['count']) {
      $form['acquia_telemetry']['check_icon'] = [
        '#prefix' => '<span class= "dashboard-check-icon">',
        '#suffix' => "</span>",
      ];
    }
    if ($this->module_handler->moduleExists($module)) {
      $module_path = $this->module_handler->getModule($module)->getPathname();
      $module_info = $this->infoParser->parse($module_path);
      $maps_api_key = $this->config('cohesion.settings')
        ->get('google_map_api_key');
      $provider = $this->loadProvider();
      if ($provider) {
        $configuration = $provider->get('configuration');
        $maps_api_key = $configuration['apiKey'];
      }
      $form['acquia_google_maps_api_wrapper'] = [
        '#type' => 'details',
        '#title' => $module_info['name'],
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];
      $form['acquia_google_maps_api_wrapper']['acquia_google_maps_api'] = [
        'maps_api_key' => [
          '#type' => 'textfield',
          '#title' => $this->t('Maps API key'),
          '#placeholder' => '1234abcd',
          '#description' => $this->t('Enter your Google Maps API Key to automatically generate maps for Place content in Acquia CMS.'),
          '#default_value' => $maps_api_key,
          '#required' => TRUE,
          '#prefix' => '<div class= "dashboard-fields-wrapper">' . $module_info['description'],
          '#suffix' => "</div>",
        ],
      ];
      $form['acquia_google_maps_api_wrapper']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => 'Save',
        '#submit' => ['::saveConfig'],
        '#prefix' => '<div class= "dashboard-buttons-wrapper">',
      ];
      $form['acquia_google_maps_api_wrapper']['actions']['ignore'] = [
        '#type' => 'submit',
        '#value' => 'Ignore',
        '#submit' => ['::ignoreConfig'],
      ];
      $form['acquia_google_maps_api_wrapper']['actions']['advanced'] = [
        '#markup' => $this->linkGenerator->generate(
          'Advanced',
          Url::fromRoute('entity.geocoder_provider.collection')
        ),
        '#suffix' => "</div>",
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
  public function saveConfig(array &$form, FormStateInterface $form_state) {
    $maps_api_key = $form_state->getValue('maps_api_key');

    // Configure Google Maps API Key for both Site Studio and
    // Geocoder module.
    $this->config('cohesion.settings')
      ->set('google_map_api_key', $maps_api_key)
      ->save(TRUE);
    $this->state->set('acquia_google_maps_progress', TRUE);
    $provider = $this->loadProvider();
    if ($provider) {
      $configuration = $provider->get('configuration');
      $configuration['apiKey'] = $maps_api_key;
      $provider->set('configuration', $configuration);
      $this->geocoderProviderStorage->save($provider);
    }

    $this->messenger()->addStatus('The Google Maps API key has been set.');
  }

  /**
   * {@inheritdoc}
   */
  public function ignoreConfig(array &$form, FormStateInterface $form_state) {
    $this->state->set('acquia_google_maps_progress', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getProgressState() {
    if ($this->module_handler->moduleExists('geocoder')) {
      return [
        'total' => 1,
        'count' => $this->state->get('acquia_google_maps_progress'),
      ];
    }
  }

}
