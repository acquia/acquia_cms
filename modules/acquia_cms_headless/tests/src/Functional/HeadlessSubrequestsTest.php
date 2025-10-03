<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\Tests\BrowserTestBase;

/**
 * Base class for the Headless Content administrator browser tests.
 *
 * @group acquia_cms
 * @group acquia_cms_headless
 * @group medium_risk
 * @group push
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
  ];

  /**
   * Disable strict config schema checks in this test.
   *
   * Scheduler has a config schema errors, and until it's fixed,
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
  protected function setUp(): void {
    // @todo Remove this check when Acquia Cloud IDEs support running functional
    // JavaScript tests.
    if (AcquiaDrupalEnvironmentDetector::isAhIdeEnv()) {
      $this->markTestSkipped('This test cannot run in an Acquia Cloud IDE.');
    }
    parent::setUp();
    $this->drupalCreateContentType([
      'type' => 'test',
      'name' => 'Test',
    ]);
    for ($i = 1; $i <= 5; $i++) {
      $this->drupalCreateNode([
        'type' => 'test',
        'title' => 'Headless Test Page ' . $i,
        'status' => 'published',
      ]);
    }
  }

  /**
   * Tests that subrequests work properly with Page Cache enabled.
   */
  public function testPageCache(): void {
    $account = $this->drupalCreateUser();
    $account->addRole('headless');
    $account->save();

    $this->drupalLogin($account);
    for ($i = 1; $i <= 5; $i++) {
      $node = $this->container->get('entity_type.manager')
        ->getStorage('node')->load($i);
      $blueprint = [
        [
          "requestId" => "router",
          "action" => "view",
          "uri" => "/router/translate-path?path=/node/" . $node->id() . "&_format=json",
        ],
        [
          "requestId" => "resolvedResource",
          "action" => "view",
          "uri" => "{{router.body@$.jsonapi.individual}}",
          "waitFor" => ["router"],
        ],
      ];

      $options = [
        'query' => [
          'query' => json_encode($blueprint, JSON_UNESCAPED_SLASHES),
        ],
      ];
      $headers = [
        'Content-Type' => 'application/json',
      ];
      $this->drupalGet('/subrequests', $options, $headers);
      $assert_session = $this->assertSession();
      $assert_session->statusCodeEquals(207);
      $assert_session->responseContains('Headless Test Page ' . $i);
    }
  }

}
