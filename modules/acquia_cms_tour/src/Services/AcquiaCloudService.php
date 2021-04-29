<?php

namespace Drupal\acquia_cms_tour\Services;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * AcquiaCloudService to automated provisioning of Search Cores.
 */
class AcquiaCloudService {
  use StringTranslationTrait;

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
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The auth token.
   *
   * @var string|null
   */
  protected $authToken;

  /**
   * Constructs a new AcmsService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The guzzle client.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(
    ClientInterface $http_client,
    StateInterface $state,
    LoggerChannelFactory $logger_factory,
    MessengerInterface $messenger
  ) {
    $this->httpClient = $http_client;
    $this->state = $state;
    $this->messenger = $messenger;
    $this->loggerFactory = $logger_factory->get('acquia_cms_tour');
    $this->authToken = NULL;
  }

  /**
   * Create a search index for specified env.
   *
   * @param string $env_name
   *   The name of environment.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function createSearchIndex(string $env_name) {
    $env_uuid = $this->getEnvironmentUuid($env_name);
    $database_name = $this->getEnvironmentDatabaseName($env_uuid);
    if ($env_uuid && $database_name) {
      $options = [
        'headers' => [
          'Authorization' => 'Bearer ' . $this->authToken,
          'Accept' => 'application/json',
        ],
      ];
      $uri = 'https://cloud.acquia.com/api/environments/' . $env_uuid . '/search/indexes';
      try {
        $request = $this->httpClient->request('GET', $uri, $options);
        if ($request->getStatusCode() == 200) {
          $search_indexes = json_decode($request->getBody()->getContents(), TRUE);
          if ($search_indexes['total'] >= 1) {
            $indexes = $search_indexes['_embedded']['items'];
            foreach ($indexes as $index) {
              // Check that index exists and has active or progress state.
              $index_status = ['active', 'in-progress'];
              if ($index['environment_id'] == $env_uuid && in_array($index['status'], $index_status)) {
                $this->messenger->addStatus($this->t('Search index [@index_id] is already exists and @status', [
                  '@index_id' => $index['id'],
                  '@status' => $index['status'],
                ]));
                break;
              }
            }
          }
          // Create search index for specified environment.
          else {
            $this->createAcquiaSolrSearchIndex($env_uuid, $database_name);
          }
        }
      }
      catch (GuzzleException $guzzleException) {
        $this->loggerFactory->error('@error', ['@error' => $guzzleException->getMessage()]);
        $this->messenger->addError($this->t('Unable to get search index, please check logs for more details.'));
      }
    }
  }

  /**
   * API call to acquia cloud for creating search index.
   *
   * @param string $env_uuid
   *   The environment uuid.
   * @param string $database_name
   *   Name of database.
   */
  private function createAcquiaSolrSearchIndex(string $env_uuid, string $database_name) {
    $options = [
      'headers' => [
        'Authorization' => 'Bearer ' . $this->authToken,
        'Accept' => 'application/json',
      ],
      'json' => [
        'database_role' => $database_name,
      ],
    ];
    $uri = 'https://cloud.acquia.com/api/environments/' . $env_uuid . '/search/indexes';
    try {
      $request = $this->httpClient->request('POST', $uri, $options);
      if ($request->getStatusCode() == 202) {
        $search_index = json_decode($request->getBody()->getContents(), TRUE);
        $this->messenger->addStatus($this->t('@message', ['@message' => $search_index['message']]));
        $notifications = $search_index['_links']['notification']['href'];
        $batch = [
          'title' => $this->t('Creating acquia search solr index.'),
          'operations' => [
            [
              '\Drupal\acquia_cms_tour\SearchSolrBatchOperation::checkSolrIndexStatus',
              [$notifications],
            ],
          ],
          'finished' => '\Drupal\example_batch\SearchSolrBatchOperation::batchFinishedCallback',
        ];
        batch_set($batch);
      }
    }
    catch (GuzzleException $guzzleException) {
      $this->loggerFactory->error('@error', ['@error' => $guzzleException->getMessage()]);
      $this->messenger->addError($this->t('Unable to create search index, please check logs for more details.'));
    }
  }

  /**
   * Create batch to monitor status of solr search index.
   *
   * Once index get created successfully,
   * api return status as completed. We can utilize that api
   * to show progress through batch on our end.
   *
   * @param string $notifications
   *   The notification url.
   *
   * @return bool
   *   The index status.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function checkSolrIndexStatus(string $notifications): bool {
    $auth_token = $this->authToken ?? $this->getApiToken();
    $options = [
      'headers' => [
        'Authorization' => 'Bearer ' . $auth_token,
        'Accept' => 'application/json',
      ],
    ];
    try {
      $request = $this->httpClient->request('GET', $notifications, $options);
      if ($request->getStatusCode() == 200) {
        $body_content = json_decode($request->getBody()->getContents(), TRUE);
        if ($body_content['status'] == 'in-progress') {
          return TRUE;
        }
        return FALSE;
      }
    }
    catch (GuzzleException $ge) {
      $this->loggerFactory->error('@error', ['@error' => $ge->getMessage()]);
      $this->messenger->addError($this->t('Unable to get solr index status, please check logs for more details.'));
    }
    return FALSE;
  }

  /**
   * Get environment database name.
   *
   * @param string $env_uuid
   *   The environment uuid.
   *
   * @return mixed|null
   *   The name of database
   */
  private function getEnvironmentDatabaseName(string $env_uuid) {
    $options = [
      'headers' => [
        'Authorization' => 'Bearer ' . $this->authToken,
        'Accept' => 'application/json',
      ],
    ];
    $uri = 'https://cloud.acquia.com/api/environments/' . $env_uuid . '/databases';
    try {
      $request = $this->httpClient->request('GET', $uri, $options);
      if ($request->getStatusCode() == 200) {
        $body_content = json_decode($request->getBody()->getContents(), TRUE);
        if (isset($body_content['_embedded']['items'])) {
          $database_name = $body_content['_embedded']['items'][0]['name'];
          return $database_name ?? NULL;
        }
      }
    }
    catch (GuzzleException $ge) {
      $this->loggerFactory->error('@error', ['@error' => $ge->getMessage()]);
      $this->messenger->addError($this->t('Unable to get environment database name, please check logs for more details.'));
    }
    return NULL;
  }

  /**
   * Get environment UUID.
   *
   * @param string $current_env_name
   *   The current environment uuid.
   *
   * @return mixed|null
   *   environment ID or null.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function getEnvironmentUuid(string $current_env_name) {
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
              break;
            }
          }
        }
        return $env_id ?? NULL;
      }
    }
    catch (GuzzleException $ge) {
      $this->loggerFactory->error('@error', ['@error' => $ge->getMessage()]);
      $this->messenger->addError($this->t('Unable to get environment ID, please check logs for more details.'));
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
        $this->authToken = $body_content['access_token'];
        return $body_content['access_token'] ?? NULL;
      }
    }
    catch (GuzzleException $guzzleException) {
      $this->loggerFactory->error('@error', ['@error' => $guzzleException->getMessage()]);
      $this->messenger->addError($this->t('Unable to get auth token, please check logs for more details.'));
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
