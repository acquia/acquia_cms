<?php

namespace Drupal\Tests\acquia_cms_document\Functional;

use Drupal\Tests\acquia_cms_common\Functional\MediaPermissionsTestBase;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_video
 * @group acquia_cms
 * @group risky
 */
class VideoPermissionsTest extends MediaPermissionsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_common',
    'acquia_cms_video',
  ];

  /**
   * {@inheritdoc}
   */
  public function getBundle(): string {
    return "video";
  }

}
