<?php

namespace Drupal\Tests\acquia_cms_image\Functional;

use Drupal\Tests\acquia_cms_common\Functional\MediaPermissionsTestBase;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_image
 * @group acquia_cms
 * @group risky
 */
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
