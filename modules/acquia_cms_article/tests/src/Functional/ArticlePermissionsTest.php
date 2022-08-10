<?php

namespace Drupal\Tests\acquia_cms_article\Functional;

use Drupal\Tests\acquia_cms_common\Functional\ContentPermissionsTestBase;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_article
 * @group acquia_cms
 * @group risky
 */
class ArticlePermissionsTest extends ContentPermissionsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_article',
  ];

  /**
   * {@inheritdoc}
   */
  public function getBundle(): string {
    return "article";
  }

}
