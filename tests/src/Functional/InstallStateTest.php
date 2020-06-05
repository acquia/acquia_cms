<?php

namespace Drupal\Tests\acquia_cms\Functional;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests config values that are set at install.
 *
 * @group acquia_cms
 */
class InstallStateTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'acquia_cms';

  /**
   * Disable strict config schema checks.
   *
   * Cohesion has several minor flaws in their config schema, and until the
   * following issues are fixed upstream, this test cannot do strict config
   * schema checks:
   *
   * - cohesion_settings.image_browser has no schema.
   * - field.storage_settings.cohesion_entity_reference_revisions has no schema.
   * - The cohesion_template:custom property has 'bool' as its type, but it
   *   be 'boolean'.
   * - The cohesion_templates.cohesion_content_templates.*:default property has
   *   'bool' as its type, but it should be 'boolean'.
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
    // At the moment, this test does not work if Cohesion will be installed due
    // to a bizarre permissions issue when drupal_valid_test_ua() tries to
    // create a .htkey file in the test site's files directory. To disable
    // Cohesion in a non-interactive installation, we need to ensure that the
    // COHESION_API_KEY and COHESION_ORG_KEY environment variables are not set.
    putenv('COHESION_API_KEY');
    putenv('COHESION_ORG_KEY');
    parent::setUp();
  }

  /**
   * Assert that all install tasks have done what they should do.
   *
   * See acquia_cms_install_tasks().
   */
  public function testConfig() {
    // Check that the default and admin themes are set as expected.
    $theme_config = $this->config('system.theme');
    $this->assertSame('cohesion_theme', $theme_config->get('default'));
    $this->assertSame('claro', $theme_config->get('admin'));

    // Check that the node create form is using the admin theme.
    $this->assertTrue($this->config('node.settings')->get('use_admin_theme'));

    // Check that the Categories and Tags vocabularies exist.
    $this->assertInstanceOf(Vocabulary::class, Vocabulary::load('categories'));
    $this->assertInstanceOf(Vocabulary::class, Vocabulary::load('tags'));

    $this->doPageContentTypeTest();
  }

  /**
   * Tests the Page content type that ships with Acquia CMS.
   */
  private function doPageContentTypeTest() {
    $assert_session = $this->assertSession();
    $this->drupalLogin($this->rootUser);

    $node = $this->createNode([
      'type' => 'page',
      'title' => 'Page Test Title',
      'moderation_state' => 'published',
    ]);
    $this->assertTrue($node->hasField('field_categories'));
    $this->assertTrue($node->hasField('field_tags'));
    $this->drupalGet($node->toUrl());
    // Test that Pathauto is working as expected.
    $assert_session->statusCodeEquals(200);
    $assert_session->addressEquals('/page-test-title');
  }

}
