<?php

namespace Drupal\acquia_cms_tour\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geocoder\GeocoderProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to configure the API key for Google Maps.
 */
final class AcquiaGoogleMapsAPIForm extends ConfigFormBase {

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
    $maps_api_key = $this->config('cohesion.settings')
      ->get('google_map_api_key');

    $provider = $this->loadProvider();
    if ($provider) {
      $configuration = $provider->get('configuration');
      $maps_api_key = $configuration['apiKey'];
    }
    $form['acquia_google_maps_api'] = [
      '#type' => 'fieldset',
      '#open' => TRUE,
      '#title' => $this->t('Google Maps'),
    ];
    $form['acquia_google_maps_api']['maps_api_key'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Maps API key'),
      '#description' => $this->t('Enter your Google Maps API Key to automatically generate maps for Place content in Acquia CMS.'),
      '#default_value' => $maps_api_key,
      '#required' => TRUE,
    ];
    $form['acquia_google_maps_api']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    return $form;
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

    $this->messenger()->addStatus('The Google Maps API key has been set.');
  }

}
