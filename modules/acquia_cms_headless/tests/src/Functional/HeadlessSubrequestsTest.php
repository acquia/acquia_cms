<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\Tests\BrowserTestBase;

/**
 * Base class for the Headless Content administrator browser tests.
 */
class HeadlessSubrequestsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'acquia_cms_headless',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // @todo Remove this check when Acquia Cloud IDEs support running functional
    // JavaScript tests.
    if (AcquiaDrupalEnvironmentDetector::isAhIdeEnv()) {
      $this->markTestSkipped('This test cannot run in an Acquia Cloud IDE.');
    }
    parent::setUp();
    $account = $this->drupalCreateUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    // Set up a content type.
    $this->drupalCreateContentType([
      'type' => 'test',
      'name' => 'Test',
    ]);
  }

  /**
   * Content admin test.
   */
  public function testSubrequests(): void {
    $host = \Drupal::request()->getSchemeAndHttpHost();

    for ($i = 1; $i <= 5; $i++) {
      // Create test node.
      $node = $this->drupalCreateNode([
        'type' => 'test',
        'title' => 'Headless Test Page ' . $i,
        'status' => 'published',
      ]);
      $client = \Drupal::httpClient();
      $response = $client->post($host . '/subrequests?_format=json', [
        'body' => json_encode([[
          "requestId" => "router",
          "action" => "view",
          "uri" => "/router/translate-path?path=/node/" . $node->id() . "&_format=json",
          "headers" => ["Accept" => "application/vnd.api+json"],
        ],
        [
          "requestId" => "resolvedResource",
          "action" => "view",
          "uri" => "{{router.body@$.jsonapi.individual}}",
          "waitFor" => ["router"],
        ],
        ]),
        'headers' => [
          'Accept' => "application/json",
        ],
        'http_errors' => FALSE,
      ]);
      $responseData = json_decode($response->getBody());
      $body = json_decode($responseData->router->body);
      $this->assertEquals('Headless Test Page ' . $i, $body->label);

    }
  }

}
