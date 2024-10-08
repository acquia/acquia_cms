<?php

namespace Drupal\Tests\acquia_cms_document\Functional;

use Drupal\Tests\acquia_cms_common\Functional\MediaPermissionsTestBase;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_document
 * @group acquia_cms
 * @group risky
 */
class DocumentPermissionsTest extends MediaPermissionsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_document',
    'acquia_cms_common',
  ];

  /**
   * {@inheritdoc}
   */
  public function getBundle(): string {
    return "document";
  }

}
