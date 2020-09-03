<?php

namespace Drupal\Tests\acquia_cms_json_api\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\media\Entity\Media;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\jsonapi\Functional\JsonApiFunctionalTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests JSON API endpoints.
 *
 * @group acquia_cms_json_api
 * @group acquia_cms
 */
class JsonApiTest extends JsonApiFunctionalTestBase {

  use TaxonomyTestTrait;
  use MediaTypeCreationTrait;

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
  protected static $modules = [
    'media',
    'acquia_cms_json_api',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create media type for test.
    $this->createMediaType('image', [
      'id' => 'image',
      'label' => 'Image',
    ]);
    // Flusing cache to make drupal aware of the new mediatype else the media
    // endpoint will through 404.
    drupal_flush_all_caches();
  }

  /**
   * Assert that all the endpoints are working as expected.
   *
   * - node, taxonomy_term, media, and taxonomy_vocabulary are accessible.
   * - Rest all resource type should not be accessible.
   *
   * @param string[]|null $roles
   *   The user role(s) to test with, or NULL to test as an anonymous user. If
   *   this is an empty array, the test will run as an authenticated user with
   *   no additional roles.
   *
   * @dataProvider providerNonRestrictedEndpoints
   */
  public function testNonRestrictedEndpoints(?array $roles) {
    // 1. GET, POST, PATCH request to node Resource type.
    $node = $this->createNode(['type' => 'article', 'moderation_state' => 'published']);
    $this->assertEndpoint($node, 'nid', 'node', $roles);

    // 2. GET, POST, PATCH request to media Resource type.
    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Test Image',
    ]);
    $media->save();
    $this->assertEndpoint($media, 'mid', 'media', $roles);

    // 3. GET, POST, PATCH request to taxonomy_term Resource type.
    $vocab = Vocabulary::load('tags');
    $term = $this->createTerm($vocab);
    $this->assertEndpoint($term, 'tid', 'taxonomy_term', $roles);
  }

  /**
   * Asserts the GET, POST and PATCH method for the provided resources.
   *
   * @param \Drupal\Core\Entity\EntityInterface $resource
   *   An object containing all the information about the resource.
   * @param string $id
   *   Name of the ID field of the resource.
   * @param string $entity_type_id
   *   Id of the entity_type to which resource belongs to.
   * @param string[]|null $roles
   *   The user role(s) to test with, or NULL to test as an anonymous user. If
   *   this is an empty array, the test will run as an authenticated user with
   *   no additional roles.
   */
  protected function assertEndpoint(EntityInterface $resource, string $id, string $entity_type_id, ?array $roles) : void {

    $account = $this->drupalCreateUser();
    $account->addRole($roles);
    $account->save();

    // Assert GET, POST, PATCH request for the given Resource type.
    $resource_url = Url::fromUri("base:/jsonapi/{$entity_type_id}/{$resource->bundle()}");
    // Assert that GET request is allowed.
    $response = $this->request('GET', $resource_url, []);
    $this->assertEquals(200, $response->getStatusCode());
    $created_response = Json::decode($response->getBody()->__toString());
    // Assert that nid field is disabled.
    foreach ($created_response['data'] as $item) {
      $this->assertFalse(array_key_exists('drupal_internal__' . $id, $item['attributes']));
    }

    // Assert that GET request is allowed for individual resources.
    $individual_resource_url = Url::fromUri("base:/jsonapi/{$entity_type_id}/{$resource->bundle()}/{$resource->uuid()}");
    $response = $this->request('GET', $individual_resource_url, []);
    $this->assertEquals(200, $response->getStatusCode());
    $created_response = Json::decode($response->getBody()->__toString());
    // Assert that nid field is disabled.
    $this->assertFalse(array_key_exists('drupal_internal__' . $id, $created_response['data']['attributes']));

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

  /**
   * Data provider for ::testNonRestrictedEndpoints().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerNonRestrictedEndpoints() {
    return [
      'anonymous user' => [
        NULL,
      ],
      'authenticated user' => [
        [],
      ],
      'administrator' => [
        ['administrator'],
      ],
    ];
  }

}
