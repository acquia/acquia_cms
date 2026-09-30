<?php

namespace Drupal\Tests\acquia_cms_video\Functional;

use Drupal\Tests\acquia_cms_common\Functional\MediaPermissionsTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_video
 * @group acquia_cms
 * @group risky
 */
#[Group('acquia_cms_video')]
#[Group('acquia_cms')]
#[Group('risky')]
#[RunTestsInSeparateProcesses]
class VideoPermissionsTest extends MediaPermissionsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_video',
  ];

  /**
   * {@inheritdoc}
   */
  public function getBundle(): string {
    return "video";
  }

}
