<?php

namespace Drupal\Tests\acquia_cms_common\FunctionalJavascript;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\ckeditor\Traits\CKEditorTestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests the CKEditor configuration shipped with Acquia CMS.
 *
 * @group acquia_cms
 * @group acquia_cms_video
 * @group medium_risk
 * @group push
 */
class CkeditorConfigurationTest extends WebDriverTestBase {

  use CKEditorTestTrait;
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
    // Cohesion's core module has an accidental dependency on
    // cohesion_custom_styles and cohesion_website_settings when using the
    // cohesion text filter with certain CKEditor plugins enabled -- it tries
    // to load all custom style and website settings entities, but without
    // checking if those entity types are even defined first. D'oh! To work
    // around this bug, we enable cohesion_custom_styles and
    // cohesion_website_settings in the test.
    // @todo Remove this when Cohesion fixes the bug.
    // @see \Drupal\cohesion\Plugin\CKEditorPlugin\DX8InlineStylesCombo::getStyleSet()
    // @see \Drupal\cohesion\ApiPluginBase::prepareData()
    'cohesion_custom_styles',
    'cohesion_website_settings',
    'media_library',
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
  protected function setUp() {
    // @todo Remove this check when Acquia Cloud IDEs support running functional
    // JavaScript tests.
    if (AcquiaDrupalEnvironmentDetector::isAhIdeEnv()) {
      $this->markTestSkipped('This test cannot run in an Acquia Cloud IDE.');
    }
    parent::setUp();
  }

  /**
   * Tests that CKEditor is configured as we expect.
   */
  public function test() {
    $session = $this->getSession();

    $node_type = $this->drupalCreateContentType()->id();
    $this->createMediaType('image');

    $roles = [
      'content_author',
      'content_editor',
      'content_administrator',
    ];
    foreach ($roles as $role_id) {
      $account = $this->drupalCreateUser([
        "create $node_type content",
      ]);
      $account->addRole($role_id);
      $account->save();
      $this->drupalLogin($account);

      $this->drupalGet("/node/add/$node_type");

      // Ensure that only the filtered_html and cohesion formats exist.
      $formats = $session->evaluateScript('Object.keys(drupalSettings.editor.formats)');
      $this->assertSame(['filtered_html', 'cohesion'], $formats);

      $this->waitForEditor();
      $this->getEditorButton('justifyleft');
      $this->getEditorButton('justifycenter');
      $this->getEditorButton('justifyright');
      $this->getEditorButton('justifyblock');
      $this->getEditorButton('drupalmedialibrary');

      // Assert that the Format dropdown is present.
      $format = $this->assertSession()
        ->waitForElementVisible('css', '#cke_edit-body-0-value span.cke_combo__format');
      $this->assertNotEmpty($format);
    }
  }

}
