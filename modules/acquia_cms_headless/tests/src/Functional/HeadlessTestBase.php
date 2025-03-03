<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for the HeadlessDashboard web_driver tests.
 */
abstract class HeadlessTestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_headless',
    'entity_clone',
    'views'
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
    $account->addRole('headless');
    $account->save();
    $this->drupalLogin($account);

    // Place the tasks and page title blocks.
    $this->drupalPlaceBlock('local_tasks_block', ['id' => 'local-tasks', 'region' => 'content', 'theme' => 'stark']);
    $this->drupalPlaceBlock('page_title_block', ['id' => 'page-title', 'region' => 'content', 'theme' => 'stark']);

    // Visit headless dashboard.
    $this->drupalGet("/admin/headless/dashboard");
  }

}
