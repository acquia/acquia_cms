<?php

namespace Drupal\Tests\acquia_cms_place\Functional;

use Drupal\Tests\acquia_cms_common\Functional\ContentPermissionsTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_place
 * @group acquia_cms
 * @group risky
 */
#[Group('acquia_cms_place')]
#[Group('acquia_cms')]
#[Group('risky')]
#[RunTestsInSeparateProcesses]
class PlacePermissionsTest extends ContentPermissionsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_place',
  ];

  /**
   * {@inheritdoc}
   */
  public function getBundle(): string {
    return "place";
  }

}
