<?php

namespace Drupal\Tests\acquia_cms_page\Functional;

use Drupal\Tests\acquia_cms_common\Functional\ContentPermissionsTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_page
 * @group acquia_cms
 * @group risky
 */
#[Group('acquia_cms_page')]
#[Group('acquia_cms')]
#[Group('risky')]
#[RunTestsInSeparateProcesses]
class PagePermissionsTest extends ContentPermissionsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_page',
  ];

  /**
   * {@inheritdoc}
   */
  public function getBundle(): string {
    return "page";
  }

}
