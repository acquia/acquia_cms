<?php

namespace Drupal\Tests\acquia_cms_event\Functional;

use Drupal\Tests\acquia_cms_common\Functional\ContentPermissionsTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_event
 * @group acquia_cms
 * @group risky
 */
#[Group('acquia_cms_event')]
#[Group('acquia_cms')]
#[Group('risky')]
#[RunTestsInSeparateProcesses]
class EventPermissionsTest extends ContentPermissionsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_event',
  ];

  /**
   * {@inheritdoc}
   */
  public function getBundle(): string {
    return "event";
  }

}
