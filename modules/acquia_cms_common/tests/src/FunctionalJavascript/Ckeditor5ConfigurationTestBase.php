<?php

namespace Drupal\Tests\acquia_cms_common\FunctionalJavascript;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Provides Base class to test CKEditor5 configuration shipped with Acquia CMS.
 *
 * @group acquia_cms
 * @group medium_risk
 * @group push
 */
abstract class Ckeditor5ConfigurationTestBase extends WebDriverTestBase {

  use CKEditor5TestTrait;
  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_common',
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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // @todo Remove this check when Acquia Cloud IDEs support running functional
    // JavaScript tests.
    if (AcquiaDrupalEnvironmentDetector::isAhIdeEnv()) {
      $this->markTestSkipped('This test cannot run in an Acquia Cloud IDE.');
    }
    parent::setUp();
  }

  /**
   * Tests that CKEditor5 is configured as we expect.
   */
  public function test() {
    $session = $this->getSession();

    $node_type = $this->drupalCreateContentType()->id();

    $account = $this->drupalCreateUser([
      "create $node_type content",
      "use text format full_html",
      "use text format filtered_html",
    ]);
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet("/node/add/$node_type");

    // Ensure that text format 'filtered_html' & 'full_html' exists.
    $formats = $session->evaluateScript('Object.keys(drupalSettings.editor.formats)');
    $this->assertSame(['filtered_html', 'full_html'], $formats);
    // Resize window, so that all Ckeditor plugins are displayed.
    $this->getSession()->getDriver()->resizeWindow(2000, 2000);
    $this->waitForEditor();
    foreach ($this->getEditorButtons() as $button) {
      $this->getEditorButton($button);
    }
  }

  /**
   * Provides an array of CkEditor plugin names.
   *
   * @return string[]
   *   Returns an array of ckeditor5 enabled toolbar plugin.
   */
  abstract protected function getEditorButtons(): array;

}
