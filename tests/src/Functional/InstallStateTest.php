<?php

namespace Drupal\Tests\acquia_cms\ExistingSite;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

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
   * Assert that all install tasks have done what they should do.
   *
   * See acquia_cms_install_tasks().
   */
  public function testConfig() {
    // Check that the admin role has been created, and that user 1
    // is set as an admin.
    $account = User::load(1);
    $this->assertInstanceOf(User::class, $account);
    /** @var \Drupal\user\UserInterface $account */
    $this->assertTrue($account->hasRole('administrator'));

    // Check that the default and admin themes are set as expected.
    $theme_config = $this->config('system.theme');
    $this->assertSame('cohesion_theme', $theme_config->get('default'));
    $this->assertSame('claro', $theme_config->get('admin'));

    // Check that the node create form is using the admin theme.
    $this->assertTrue($this->config('node.settings')->get('use_admin_theme'));

    // Check that the Cohesion API keys are set.
    $api_key = getenv('COHESION_API_KEY');
    $org_key = getenv('COHESION_ORG_KEY');
    $this->assertNotEmpty($api_key);
    $this->assertNotEmpty($org_key);
    $cohesion_config = $this->config('cohesion.settings');
    $this->assertSame($api_key, $cohesion_config->get('api_key'));
    $this->assertSame($org_key, $cohesion_config->get('organization_key'));

    $this->doArticleContentTypeTest();
  }

  /**
   * Tests the Article content type that ships with Acquia CMS.
   */
  private function doArticleContentTypeTest() {
    $assert_session = $this->assertSession();
    $this->drupalLogin($this->rootUser);

    $node = $this->createNode([
      'type' => 'article',
      'title' => 'Article Test Title',
      'moderation_state' => 'published',
    ]);
    $this->drupalGet($node->toUrl());
    // Test that pathauto is working as expected.
    $assert_session->statusCodeEquals(200);
    $assert_session->addressEquals('/article/article-test-title');
  }

}
