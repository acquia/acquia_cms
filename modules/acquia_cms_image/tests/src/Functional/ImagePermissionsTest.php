<?php

namespace Drupal\Tests\acquia_cms_image\Functional;

use Drupal\Tests\acquia_cms_common\Functional\MediaPermissionsTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_image
 * @group acquia_cms
 * @group risky
 */
#[Group('acquia_cms_image')]
#[Group('acquia_cms')]
#[Group('risky')]
#[RunTestsInSeparateProcesses]
class ImagePermissionsTest extends MediaPermissionsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_image',
  ];

  /**
   * {@inheritdoc}
   */
  public function getBundle(): string {
    return "image";
  }

}
