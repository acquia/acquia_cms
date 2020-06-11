<?php

namespace Drupal\Tests\acquia_cms_common\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests the CKEditor configuration shipped with Acquia CMS.
 *
 * @todo Add this to the acquia_cms group when Acquia Cloud IDEs support running
 *   functional JavaScript tests.
 */
class CkeditorConfigurationTest extends WebDriverTestBase {

  use MediaTypeCreationTrait;

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
   * Data provider for ::test().
   *
   * @return array
   *   The sets of arguments to pass to the test method.
   */
  public function roleProvider() {
    return [
      ['content_author'],
      ['content_editor'],
      ['content_administrator'],
    ];
  }

  /**
   * Tests that CKEditor is configured as we expect.
   *
   * @param string $role_id
   *   The machine name of the user role to test with.
   *
   * @dataProvider roleProvider
   */
  public function test(string $role_id) {
    $session = $this->getSession();

    $node_type = $this->drupalCreateContentType()->id();
    user_role_grant_permissions($role_id, ["create $node_type content"]);

    $this->createMediaType('image');

    $account = $this->drupalCreateUser();
    $account->addRole($role_id);
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet("/node/add/$node_type");

    // Ensure that only the filtered_html format exists.
    $formats = $session->evaluateScript('Object.keys(drupalSettings.editor.formats)');
    $this->assertSame(['filtered_html'], $formats);

    // Wait for CKEditor to initialize so we can inspect its commands.
    $commands = 'CKEDITOR.instances["edit-body-0-value"].commands';
    $this->assertJsCondition("typeof $commands === 'object'");

    // Ensure that all the expected commands are loaded.
    $this->assertJsCondition("'drupalmedialibrary' in $commands");
    $this->assertJsCondition("'justifyleft' in $commands");
    $this->assertJsCondition("'justifycenter' in $commands");
    $this->assertJsCondition("'justifyright' in $commands");
    $this->assertJsCondition("'justifyblock' in $commands");
  }

}
