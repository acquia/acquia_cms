<?php

namespace Drupal\Tests\acquia_cms_document\Functional;

use Drupal\Tests\acquia_cms_common\Functional\MediaPermissionsTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_document
 * @group acquia_cms
 * @group risky
 */
#[Group('acquia_cms_document')]
#[Group('acquia_cms')]
#[Group('risky')]
#[RunTestsInSeparateProcesses]
class DocumentPermissionsTest extends MediaPermissionsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_document',
  ];

  /**
   * {@inheritdoc}
   */
  public function getBundle(): string {
    return "document";
  }

}
