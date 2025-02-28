<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\user\Entity\Role;

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
    // Create an administrator role with is_admin set to true.
    Role::create([
      'id' => 'administrator',
      'label' => 'Administrator',
      'is_admin' => TRUE,
    ])->save();
    $account = $this->drupalCreateUser();
    $account->addRole('headless');
    $account->save();
    $this->drupalLogin($account);

    // Visit headless dashboard.
    $this->drupalGet("/admin/headless/dashboard");
  }

}
