<?php

namespace Drupal\Tests\acquia_cms_place\Functional;

use Drupal\Tests\acquia_cms_common\Functional\ContentPermissionsTestBase;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_place
 * @group acquia_cms
 * @group risky
 */
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
