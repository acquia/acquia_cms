<?php

namespace Drupal\acquia_cms_tour\Services;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * AcquiaCloudService to automated provisioning of Search Cores.
 */
class AcquiaCloudService {

  /**
   * Guzzle\Client instance.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Constructs a new AcmsService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The guzzle client.
   * @param \Drupal\Core\State\StateInterface $state
   *   The logger factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ClientInterface $http_client,
    StateInterface $state,
    LoggerChannelFactory $logger_factory
  ) {
    $this->httpClient = $http_client;
    $this->state = $state;
    $this->loggerFactory = $logger_factory->get('acquia_cms_tour');
  }

  /**
   * Create a search index for specified env.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function createSearchIndex($env_name) {
    $this->getEnvironmentUuid($env_name);
    // @todo first check if search index already exists
    // else create one for specified environment.
  }

  /**
   * Get environment UUID.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function getEnvironmentUuid($current_env_name) {
    $api_token = $this->getApiToken();
    $options = [
      'headers' => [
        'Authorization' => 'Bearer ' . $api_token,
        'Accept' => 'application/json',
      ],
    ];
    $client_uuid = $this->state->get('acquia_search.uuid');
    $uri = 'https://cloud.acquia.com/api/applications/' . $client_uuid . '/environments';
    try {
      $request = $this->httpClient->request('GET', $uri, $options);
      if ($request->getStatusCode() == 200) {
        $body_content = json_decode($request->getBody()->getContents(), TRUE);
        // Get all available environments for the application and
        // loop through to get the matching id of current env.
        if (isset($body_content['_embedded']['items'])) {
          $environments = $body_content['_embedded']['items'];
          foreach ($environments as $env) {
            if ($env['name'] == $current_env_name) {
              $env_id = $env['id'];
            }
          }
        }
        return $env_id ?? NULL;
      }
    }
    catch (GuzzleException $ge) {
      $this->loggerFactory->error('@error', ['@error' => $ge->getMessage()]);
    }
    return NULL;
  }

  /**
   * Get auth token.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function getApiToken() {
    $options = [
      'json' => [
        'client_id' => $this->getClientId(),
        'client_secret' => $this->getClientSecret(),
        'grant_type' => 'client_credentials',
      ],
    ];
    $uri = 'https://accounts.acquia.com/api/auth/oauth/token';
    try {
      $request = $this->httpClient->request('POST', $uri, $options);
      if ($request->getStatusCode() == 200) {
        $body_content = json_decode($request->getBody()->getContents(), TRUE);
        return $body_content['access_token'] ?? NULL;
      }
    }
    catch (GuzzleException $guzzleException) {
      $this->loggerFactory->error('@error', ['@error' => $guzzleException->getMessage()]);
    }
    return NULL;
  }

  /**
   * Get client ID.
   */
  private function getClientId() {
    return $this->state->get('acquia_search.cloud_api_key') ?? NULL;
  }

  /**
   * Get client secret.
   */
  private function getClientSecret() {
    return $this->state->get('acquia_search.cloud_api_secret') ?? NULL;
  }

}
