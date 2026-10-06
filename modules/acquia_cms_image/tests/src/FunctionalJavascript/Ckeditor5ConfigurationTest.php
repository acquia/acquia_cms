<?php

namespace Drupal\Tests\acquia_cms_image\FunctionalJavascript;

use Drupal\Tests\acquia_cms_common\FunctionalJavascript\Ckeditor5ConfigurationTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the CKEditor5 configuration shipped with Acquia CMS.
 *
 * @group acquia_cms
 * @group acquia_cms_image
 * @group medium_risk
 * @group push
 */
#[Group('acquia_cms')]
#[Group('acquia_cms_image')]
#[Group('medium_risk')]
#[Group('push')]
#[RunTestsInSeparateProcesses]
class Ckeditor5ConfigurationTest extends Ckeditor5ConfigurationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_image',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getEditorButtons(): array {
    return ["Insert Media", "Insert images using Imce File Manager"];
  }

}
