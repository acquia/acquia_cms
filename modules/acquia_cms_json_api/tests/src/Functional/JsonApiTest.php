<?php

namespace Drupal\Tests\acquia_cms_json_api\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\acquia_cms_common\Traits\MediaTestTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\jsonapi\Functional\JsonApiRequestTestTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests JSON API endpoints.
 *
 * @group acquia_cms_json_api
 * @group acquia_cms
 */
class JsonApiTest extends BrowserTestBase {

  use MediaTestTrait;
  use TaxonomyTestTrait;
  use JsonApiRequestTestTrait;

  /**
   * Disable strict config schema checks in this test.
   *
   * Cohesion has a lot of config schema errors, and until they are all fixed,
   * this test cannot pass unless we disable strict config schema checking
   * altogether. Since strict config schema isn't critically important in
   * testing this functionality, it's okay to disable it for now, but it should
   * be re-enabled (i.e., this property should be removed) as soon as possible.
   *
   * @var bool
   */
  // @codingStandardsIgnoreStart
  protected $strictConfigSchema = FALSE;
  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_article',
    'acquia_cms_image',
    'acquia_cms_json_api',
  ];

  /**
   * Assert that all the endpoints are working as expected.
   *
   * - node, taxonomy_term, media, and taxonomy_vocabulary are accessible.
   * - Rest all resource type should not be accessible.
   */
  public function testNonRestrictedEndpoints() {
    // 1. GET, POST, PATCH request to node Resource type.
    $node = $this->createNode(['type' => 'article', 'moderation_state' => 'published']);
    $this->assertEndpoint($node, 'nid', 'node');

    // 2. GET, POST, PATCH request to media Resource type.
    $media = $this->createMedia(['bundle' => 'image']);
    $this->assertEndpoint($media, 'mid', 'media');

    // 3. GET, POST, PATCH request to taxonomy_term Resource type.
    $vocab = Vocabulary::load('tags');
    $term = $this->createTerm($vocab);
    $this->assertEndpoint($term, 'tid', 'taxonomy_term');
  }

  /**
   * Asserts the GET, POST and PATCH method for the provided resources.
   *
   * @param mixed $resource
   *   An object containing all the information about the resource.
   * @param string $id
   *   Name of the ID field of the resource.
   * @param string $entity_type_id
   *   Id of the entity_type to which resource belongs to.
   */
  protected function assertEndpoint($resource, string $id, string $entity_type_id) : void {

    $account = $this->drupalCreateUser();
    $account->addRole('administrator');
    $account->save();

    // Assert GET, POST, PATCH request for the given Resource type.
    $resource_url = Url::fromUri('base:/jsonapi/' . $entity_type_id . '/' . $resource->bundle());
    // Assert that GET request is allowed.
    $response = $this->request('GET', $resource_url, []);
    $this->assertEquals(200, $response->getStatusCode());
    $created_response = Json::decode($response->getBody()->__toString());
    // Assert that nid field is disabled.
    foreach ($created_response['data'] as $item) {
      $this->assertFalse(array_key_exists('drupal_internal__' . $id, $item['attributes']));
    }

    $body = [
      'data' => [
        'type' => $entity_type_id . '--' . $resource->bundle(),
        'attributes' => [
          'title' => 'Custom' . $resource->bundle(),
        ],
      ],
    ];
    $response = $this->request('POST', $resource_url, [
      'body' => Json::encode($body),
      'auth' => [$account->getAccountName(), $account->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    // Assert that POST request is not allowed.
    $this->assertEquals(405, $response->getStatusCode());

    $body = [
      'data' => [
        'type' => $entity_type_id . '--' . $resource->bundle(),
        'id' => $resource->uuid(),
        'attributes' => [
          'title' => 'Updated' . $resource->bundle(),
        ],
      ],
    ];
    $response = $this->request('PATCH', $resource_url, [
      'body' => Json::encode($body),
      'auth' => [$account->getAccountName(), $account->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    // Assert that PATCH request is not allowed.
    $this->assertEquals(405, $response->getStatusCode());
  }

  /**
   * Assert that all other endpoints are not accessible.
   */
  public function testRestrictedEndpoints() {

    $account = $this->drupalCreateUser();
    $account->addRole('administrator');
    $account->save();

    // Field for testing field endpoint.
    FieldStorageConfig::create([
      'field_name' => 'field_testing_endpoint',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_testing_endpoint',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();
    $field_config = FieldConfig::loadByName('node', 'article', 'field_testing_endpoint');

    // 1. GET request to check the restriction.
    $user_url = Url::fromUri('base:/jsonapi/user/user');
    $response = $this->request('GET', $user_url, []);
    $this->assertEquals(404, $response->getStatusCode());
    // Individual user endpoint.
    $user_individual_url = Url::fromUri('base:/jsonapi/user/user/' . $account->uuid());
    $response = $this->request('GET', $user_individual_url, []);
    $this->assertEquals(404, $response->getStatusCode());

    $field_config_url = Url::fromUri('base:/jsonapi/field_config/field_config');
    $response = $this->request('GET', $field_config_url, []);
    $this->assertEquals(404, $response->getStatusCode());

    $field_config_individual_url = Url::fromUri('base:/jsonapi/field_config/field_config/' . $field_config->uuid());
    $response = $this->request('GET', $field_config_individual_url, []);
    $this->assertEquals(404, $response->getStatusCode());

    // 2. PATCH request to check the restriction.
    $body = [
      'data' => [
        'type' => 'field--config',
        'id' => $field_config->uuid(),
        'attributes' => [
          'label' => 'Custom EndPoint',
        ],
      ],
    ];
    $response = $this->request('PATCH', $field_config_individual_url, [
      'body' => Json::encode($body),
      'auth' => [$account->getAccountName(), $account->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $this->assertEquals(404, $response->getStatusCode());

    $body = [
      'data' => [
        'type' => 'user--user',
        'id' => $account->uuid(),
        'attributes' => [
          'display_name' => 'Superman',
        ],
      ],
    ];
    $response = $this->request('PATCH', $user_individual_url, [
      'body' => Json::encode($body),
      'auth' => [$account->getAccountName(), $account->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $this->assertEquals(404, $response->getStatusCode());

    // 3. POST request to check the restriction.
    $body = [
      'data' => 'field_storage_config--field_storage_config',
      'attributes' => [
        'field_name' => 'field_storage_endpoint',
        'entity_type' => 'node',
        'field_storage_config_type' => 'string',
      ],
    ];
    $field_storage_config_url = Url::fromUri('base:/jsonapi/field_storage_config/field_storage_config');
    $response = $this->request('POST', $field_storage_config_url, [
      'body' => Json::encode($body),
      'auth' => [$account->getAccountName(), $account->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $this->assertEquals(404, $response->getStatusCode());
  }

}
