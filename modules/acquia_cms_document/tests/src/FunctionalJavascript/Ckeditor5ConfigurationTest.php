<?php

namespace Drupal\Tests\acquia_cms_document\FunctionalJavascript;

use Drupal\Tests\acquia_cms_common\FunctionalJavascript\Ckeditor5ConfigurationTestBase;

/**
 * Tests the CKEditor5 configuration shipped with Acquia CMS.
 *
 * @group acquia_cms
 * @group acquia_cms_document
 * @group medium_risk
 * @group push
 */
class Ckeditor5ConfigurationTest extends Ckeditor5ConfigurationTestBase {

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
  protected function getEditorButtons(): array {
    return ["Insert Media"];
  }

}
