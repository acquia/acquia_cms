<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to configure the API key for Google Maps.
 */
final class AcquiaGoogleMapsAPIForm extends FormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * AcquiaGoogleMapsAPIForm constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
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
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Text input for Google Maps. ACMS can use the Gmaps API in two totally
    // different features (Site Studio and Place nodes). Site Studio is always
    // enabled in ACMS, but Place may not.
    $cohesion_gmaps_key = $this->config('cohesion.settings')
      ->get('google_map_api_key');

    $geocoder_gmaps_key = $this->config('geocoder.geocoder_provider.googlemaps')
      ->get('configuration.apiKey');

    $maps_api_key = $geocoder_gmaps_key ? $geocoder_gmaps_key : $cohesion_gmaps_key;

    $form['acquia_google_maps_api'] = [
      'maps_api_key' => [
        '#type' => 'textfield',
        '#title' => $this->t('Maps API key'),
        '#default_value' => $maps_api_key,
      ],
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Google Maps'),
      '#description' => $this->t(
        'Enter your Google Maps API Key to automatically generate
        maps for Place content in Acquia CMS.'
      ),
    ];
    $form['acquia_google_maps_api']['actions']['submit'] = [
      '#type' => 'submit',
      '#id' => 'maps-submit',
      '#value' => 'Save',
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if (!$form_state->getValue(['maps_api_key'])) {
      $form_state->setErrorByName(
        'maps_api_key',
        $this->t('The Google Maps API key cannot be null.')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $maps_api_key = $form_state->getValue(['maps_api_key']);

    // Configure Google Maps API Key for both Site Studio and
    // Geocoder module.
    if ($maps_api_key) {
      $this->configFactory()->getEditable('cohesion.settings')
        ->set('google_map_api_key', $maps_api_key)
        ->save(TRUE);

      // ACMS Place may not be installed, so test if it's on before setting the
      // key here.
      if ($this->moduleHandler->moduleExists('acquia_cms_place')) {
        $this->configFactory()
          ->getEditable('geocoder.geocoder_provider.googlemaps')
          ->set('configuration.apiKey', $maps_api_key)
          ->save(TRUE);
      }

      $this->messenger()->addStatus('The Google Maps API key has been set.');
    }
  }

}
