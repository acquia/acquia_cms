<?php

namespace Drupal\Tests\acquia_cms_headless\FunctionalJavascript;

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
    'acquia_cms_toolbar',
    'entity_clone',
    'views'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
