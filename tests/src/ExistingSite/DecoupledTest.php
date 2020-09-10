<?php

namespace Drupal\Tests\acquia_cms_json_api\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\acquia_cms_common\Traits\MediaTestTrait;
use Drupal\Tests\jsonapi\Functional\JsonApiRequestTestTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests JSON API endpoints.
 *
 * @group acquia_cms_json_api
 * @group acquia_cms
 */
class DecoupledTest extends ExistingSiteBase {

  use JsonApiRequestTestTrait;
  use MediaTestTrait;

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
    $this->assertEndpoint($node, 'node', $roles);

    // 2. GET, POST, PATCH request to media Resource type.
    $media = $this->createMedia([
      'bundle' => 'image',
      'name' => 'Test Image',
    ]);
    $this->markEntityForCleanup($media);
    $this->assertEndpoint($media, 'media', $roles);

    // 3. GET, POST, PATCH request to taxonomy_term Resource type.
    $vocab = Vocabulary::load('tags');
    $term = $this->createTerm($vocab);
    $this->assertEndpoint($term, 'taxonomy_term', $roles);
  }

  /**
   * Asserts the GET, POST and PATCH method for the provided resources.
   *
   * @param \Drupal\Core\Entity\EntityInterface $resource
   *   An object containing all the information about the resource.
   * @param string $entity_type_id
   *   Id of the entity_type to which resource belongs to.
   * @param string[]|null $roles
   *   The user role(s) to test with, or NULL to test as an anonymous user. If
   *   this is an empty array, the test will run as an authenticated user with
   *   no additional roles.
   */
  protected function assertEndpoint(EntityInterface $resource, string $entity_type_id, ?array $roles) : void {

    $account = $this->createUser();
    $account->addRole($roles);
    $account->save();

    // Assert GET, POST, PATCH request for the given Resource type.
    $resource_url = Url::fromUri("base:/jsonapi/{$entity_type_id}/{$resource->bundle()}");
    // Assert that GET request is allowed.
    $response = $this->request('GET', $resource_url, []);
    $this->assertEquals(200, $response->getStatusCode());

    // Assert that GET request is allowed for individual resources.
    $individual_resource_url = Url::fromUri("base:/jsonapi/{$entity_type_id}/{$resource->bundle()}/{$resource->uuid()}");
    $response = $this->request('GET', $individual_resource_url, []);
    $this->assertEquals(200, $response->getStatusCode());

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

    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();

    // Field for testing field endpoint.
    $field_config = FieldConfig::loadByName('node', 'article', 'field_tags');

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
        'field_name' => 'field_storage_tags',
        'entity_type' => 'node',
        'field_storage_config_type' => 'entity_reference',
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
