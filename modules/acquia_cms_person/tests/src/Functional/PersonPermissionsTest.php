<?php

namespace Drupal\Tests\acquia_cms_person\Functional;

use Drupal\Tests\acquia_cms_common\Functional\ContentPermissionsTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_person
 * @group acquia_cms
 * @group risky
 */
#[Group('acquia_cms_person')]
#[Group('acquia_cms')]
#[Group('risky')]
#[RunTestsInSeparateProcesses]
class PersonPermissionsTest extends ContentPermissionsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_person',
  ];

  /**
   * {@inheritdoc}
   */
  public function getBundle(): string {
    return "person";
  }

}
