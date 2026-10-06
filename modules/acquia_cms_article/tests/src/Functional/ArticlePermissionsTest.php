<?php

namespace Drupal\Tests\acquia_cms_article\Functional;

use Drupal\Tests\acquia_cms_common\Functional\ContentPermissionsTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests basic, broad permissions of the user roles included with Acquia CMS.
 *
 * @group acquia_cms_article
 * @group acquia_cms
 * @group risky
 */
#[Group('acquia_cms_article')]
#[Group('acquia_cms')]
#[Group('risky')]
#[RunTestsInSeparateProcesses]
class ArticlePermissionsTest extends ContentPermissionsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_article',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  public function getBundle(): string {
    return "article";
  }

}
