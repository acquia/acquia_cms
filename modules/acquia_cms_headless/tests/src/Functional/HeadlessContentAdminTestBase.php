<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for the HeadlessDashboard web_driver tests.
 */
abstract class HeadlessContentAdminTestBase extends WebDriverTestBase {

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

    // Visit content page.
    $this->drupalGet("admin/content");

    // Set up a content type.
    $this->drupalCreateContentType([
      'type' => 'test',
      'name' => 'Test',
      'third_party_settings' => [
        "acquia_cms_common" => [
          "workflow_id" => "editorial",
        ],
      ],
    ]);
  }

}
