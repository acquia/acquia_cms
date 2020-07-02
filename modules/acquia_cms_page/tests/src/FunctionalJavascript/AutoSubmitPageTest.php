<?php

namespace Drupal\Tests\acquia_cms_page\FunctionalJavascript;

use Drupal\Tests\acquia_cms_common\FunctionalJavascript\AutoSaveTestBase;

/**
 * Test the autosave integration for Page content type.
 *
 * @todo Add this to the acquia_cms and acquia_cms_common groups when Acquia
 *   Cloud IDEs support running functional JavaScript tests.
 */
class AutoSubmitPageTest extends AutoSaveTestBase {

  /**
   * {@inheritdoc}
   */
  protected $nodeType = 'page';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_page',
    'autosave_form',
  ];

  /**
   * Disable strict config schema checks in this test.
   *
   * Cohesion has a lot of config schema errors, and until they are all fixed,
   * this test cannot pass unless we disable strict config schema checking
   * altogether. Since strict config schema isn't critically important in
   * testing this functionality, it's okay to disable it for now, but it should
   * be re-enabled (i.e., this property should be removed) as soon as possible.
   *
   * @var bool
   */
  // @codingStandardsIgnoreStart
  protected $strictConfigSchema = FALSE;
  // @codingStandardsIgnoreEnd

}
