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
    // Create test node.
    $node = $this->drupalCreateNode([
      'type' => 'test',
      'title' => 'Headless Test Page 1',
      'status' => 'published',
    ]);
    // Curl request post fields data.
    $curl = curl_init();
    $request = [
      [
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
    ];
    $postFields = json_encode($request);

    curl_setopt_array($curl, [
      CURLOPT_URL => $host . '/subrequests?_format=json',
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => TRUE,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => $postFields,
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
      ],
    ]);

    $response = curl_exec($curl);
    curl_close($curl);
    $responseData = json_decode($response);
    $body = json_decode($responseData->router->body);
    $subrequest_response = end($responseData);
    $subrequest_body = json_decode(end($subrequest_response));
    // Assert title from first request.
    $this->assertEquals('Headless Test Page 1', $body->label, "The node title in 'router' matches");
    // Assert title from sub request.
    $this->assertEquals('Headless Test Page 1', $subrequest_body->data->attributes->title, "The node title in 'subrequest' matches");
  }

}
