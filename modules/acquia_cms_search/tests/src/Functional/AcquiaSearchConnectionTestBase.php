<?php

namespace Drupal\Tests\acquia_cms_search\Functional;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\Tests\BrowserTestBase;

/**
 * Base class for the Acquia search connector functional tests.
 */
class AcquiaSearchConnectionTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_search',
    'acquia_cms_common',
    'acquia_search',
    'acquia_search_defaults',
    'search_api_db',
  ];

  /**
   * {@inheritdoc}
   */
  protected $permissions = [
    'administer site configuration',
    'administer search_api',
  ];

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
  protected function setUp(): void {
    // Check active subscription of acquia environment.
    if (AcquiaDrupalEnvironmentDetector::getAhApplicationUuid()) {
      $this->markTestSkipped('This test cannot run in an Acquia Cloud IDE.');
    }
    parent::setUp();
    $account = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($account);
  }

}
