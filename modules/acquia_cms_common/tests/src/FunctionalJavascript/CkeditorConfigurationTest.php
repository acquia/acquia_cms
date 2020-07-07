<?php

namespace Drupal\Tests\acquia_cms_common\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\ckeditor\Traits\CKEditorTestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests the CKEditor configuration shipped with Acquia CMS.
 *
 * @todo Add this to the acquia_cms and acquia_cms_common groups when Acquia
 *   Cloud IDEs support running functional JavaScript tests.
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
   * Tests that CKEditor is configured with the buttons and plugins we expect.
   */
  public function test() {
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

      // Ensure that only the filtered_html format exists.
      $formats = $this->getSession()
        ->evaluateScript('Object.keys(drupalSettings.editor.formats)');
      $this->assertSame(['filtered_html'], $formats);

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
