<?php

namespace Drupal\Tests\acquia_cms\ExistingSite;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\acquia_cms_common\Traits\MediaTestTrait;
use Drupal\Tests\jsonapi\Functional\JsonApiRequestTestTrait;
use Drupal\user\Entity\Role;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests access to JSON:API endpoints.
 *
 * @group acquia_cms
 * @group profile
 */
class DecoupledTest extends ExistingSiteBase {

  use JsonApiRequestTestTrait;
  use MediaTestTrait;

  /**
   * Tests that the out-of-the-box JSON:API endpoints work as expected.
   *
   * @param string[]|null $roles
   *   The user role(s) to test with, or NULL to test as an anonymous user. If
   *   this is an empty array, the test will run as an authenticated user with
   *   no additional roles.
   *
   * @dataProvider providerRoles
   */
  public function testResourceTypes(?array $roles) {
    if (isset($roles)) {
      $account = $this->createUser();
      array_walk($roles, [$account, 'addRole']);
      $account->save();
      $this->setCurrentUser($account);
    }
    else {
      $this->assertFalse($this->container->get('current_user')->isAuthenticated());
    }

    // The node resource type should be enabled, which means we should be able
    // to GET, but not PATCH or POST.
    $node = $this->createNode([
      'type' => 'article',
      'moderation_state' => 'published',
    ]);
    $this->assertResourceType(TRUE, $node, [
      'POST' => [
        'type' => 'article',
        'title' => $this->randomString(),
      ],
      'PATCH' => [
        'title' => $this->randomString(),
      ],
    ]);

    // The media resource type should be enabled, so we should be able to GET,
    // but not PATCH or POST.
    $media = $this->createMedia([
      'bundle' => 'image',
      'name' => 'Test Image',
    ]);
    $this->markEntityForCleanup($media);
    $this->assertResourceType(TRUE, $media, [
      'POST' => [
        'bundle' => 'image',
        'name' => 'Test image',
      ],
      'PATCH' => [
        'name' => 'Test image',
      ],
    ]);

    // The taxonomy term resource type should be enabled, which means we should
    // be able to GET, but not PATCH or POST.
    $term = $this->createTerm(Vocabulary::load('tags'));
    $this->assertResourceType(TRUE, $term, [
      'POST' => [
        'name' => 'Pastafazoul',
      ],
      'PATCH' => [
        'name' => 'Pastafazoul',
      ],
    ]);

    // The user resource type should be disabled, so we should not be able to
    // do anything with it.
    $this->assertResourceType(FALSE, $this->createUser(), [
      'PATCH' => [
        'display_name' => 'Superman',
      ],
    ]);

    // The field_config resource type should be disabled, so we should not be
    // able to do anything with it.
    $field = FieldConfig::loadByName('node', 'article', 'field_tags');
    $this->assertResourceType(FALSE, $field, [
      'PATCH' => [
        'label' => 'Custom EndPoint',
      ],
      'POST' => [
        'field_name' => 'field_storage_tags',
        'entity_type' => 'node',
        'field_storage_config_type' => 'entity_reference',
      ],
    ]);
    // The user_role resource type should be disabled, so we should not be able
    // to do anything with it.
    $role = Role::load('administrator');
    $this->assertResourceType(FALSE, $role, [
      'PATCH' => [
        'label' => 'Buggy administrator',
      ],
      'POST' => [
        'is_admin' => FALSE,
        'status' => FALSE,
      ],
    ]);
    // The file resource type should be enabled, which means we should be able
    // to GET, but not PATCH or POST.
    $file = File::create([
      'uri' => 'public://test.txt',
      'uid' => 1,
    ]);
    $file->save();
    $this->markEntityForCleanup($file);
    $this->assertResourceType(TRUE, $file, [
      'PATCH' => [
        'filename' => 'Hello World',
      ],
      'POST' => [
        'status' => FALSE,
      ],
    ]);
    // The date_format type should be enabled, which means we should be able to
    // GET, but not PATCH or POST.
    $date_format = DateFormat::load('medium');
    $this->assertResourceType(TRUE, $date_format, [
      'PATCH' => [
        'label' => 'Cool date',
      ],
      'POST' => [
        'pattern' => 'D, f',
      ],
    ]);
  }

  /**
   * Asserts that a resource type is either enabled or disabled.
   *
   * Enabled resource types are expected to be readable (GET should succeed for
   * both individual resources and collections) but not writeable (POST and
   * PATCH should fail). Disabled resource types should always produce a 404 no
   * matter what we try to do.
   *
   * @param bool $is_enabled
   *   TRUE if the resource type is enabled, FALSE otherwise.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A sample entity of this resource type, to test reading and writing
   *   individual resources.
   * @param array[] $attributes
   *   Arrays of attributes to send when testing the POST and PATCH methods,
   *   keyed by method (e.g., 'POST' => [...], 'PATCH' => [...]).
   */
  private function assertResourceType(bool $is_enabled, EntityInterface $entity, array $attributes) : void {
    $resource_type = $entity->getEntityTypeId() . '--' . $entity->bundle();
    $base_uri = '/jsonapi/' . str_replace('--', '/', $resource_type);
    $resource_uri = $base_uri . '/' . $entity->uuid();

    $assert_status = function (string $method, string $url, array $request_options, int $expected_status) {
      $url = Url::fromUri("base:$url");
      $status = $this->request($method, $url, $request_options)->getStatusCode();
      $this->assertSame($expected_status, $status);
    };

    $request_options = [];

    // If this resource type is enabled, we should be able to read both the
    // individual resource and a collection of this resource type. Otherwise,
    // we should just get a 404.
    $expected_status = $is_enabled ? 200 : 404;
    $assert_status('GET', $base_uri, $request_options, $expected_status);
    $assert_status('GET', $resource_uri, $request_options, $expected_status);

    $account = $this->container->get('current_user')->getAccount();
    if ($account->isAuthenticated()) {
      $request_options['auth'] = [
        $account->getAccountName(),
        $account->passRaw,
      ];
    }
    $request_options['headers']['Content-Type'] = 'application/vnd.api+json';

    // If this resource type is enabled, we should be get a 405 when we attempt
    // to POST or PATCH. Otherwise we should just get a 404.
    $expected_status = $is_enabled ? 405 : 404;

    // Assert that we cannot create a new resource of this type.
    if (isset($attributes['POST'])) {
      $request_options['body'] = Json::encode([
        'data' => [
          'type' => $resource_type,
          'attributes' => $attributes['POST'],
        ],
      ]);
      $assert_status('POST', $base_uri, $request_options, $expected_status);
    }

    // Assert that we cannot update an existing resource of this type.
    if (isset($attributes['PATCH'])) {
      $request_options['body'] = Json::encode([
        'data' => [
          'type' => $resource_type,
          'id' => $entity->uuid(),
          'attributes' => $attributes['PATCH'],
        ],
      ]);
      $assert_status('PATCH', $resource_uri, $request_options, $expected_status);
    }
  }

  /**
   * Data provider for ::testResourceTypes().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerRoles() {
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
