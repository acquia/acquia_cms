<?php

namespace Drupal\Tests\acquia_cms\ExistingSite;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\acquia_cms_common\Traits\MediaTestTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests config values that are set at install.
 *
 * @group acquia_cms
 * @group profile
 * @group risky
 */
class InstallStateTest extends ExistingSiteBase {

  use MediaTestTrait {
    createMedia as traitCreateMedia;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Update configuration so that password policy can be tested by
    // registering an account through UI.
    $this->container->get('config.factory')
      ->getEditable('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', 'visitors_admin_approval')
      ->save();
  }

  /**
   * Tests that key administrative pages are available.
   *
   * Acquia CMS is a big and complicated system, and it is possible that simple
   * dependency updates can produce WSODs in key administrative places. To
   * detect that kind of thing, this method logs in as an administrator, visits
   * a bunch of those key administrative pages, and verifies that they produce
   * a 200 status code. That doesn't mean they work as intended, of course, but
   * at least they are not producing scary blank (or error) screens.
   */
  public function testKeyAdministrativePages() : void {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    $pages_to_check = [
      '/admin/content',
      '/admin/structure/block',
      '/admin/people',
    ];
    $session = $this->getSession();
    foreach ($pages_to_check as $path) {
      $this->drupalGet($path);
      $status_code = $session->getStatusCode();
      $this->assertSame(200, $status_code, "$path returned status code $status_code.");
    }
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
    $this->assertSame('acquia_claro', $theme_config->get('admin'));

    // Check that the node create form is using the admin theme.
    $this->assertTrue($this->config('node.settings')->get('use_admin_theme'));

    // Check page caching set for one year and css/js are aggregated.
    $performance_config = $this->config('system.performance');
    $this->assertSame(31536000, $performance_config->get('cache.page.max_age'));
    $this->assertSame(TRUE, $performance_config->get('css.preprocess'));
    $this->assertSame(TRUE, $performance_config->get('js.preprocess'));

    // Check purge configurations incorporating acquia purge.
    $purge_plugin_config = $this->config('purge.plugins');
    $purgers = $purge_plugin_config->get('purgers');
    $this->assertSame('acquia_purge', $purgers[0]['plugin_id']);
    $this->assertSame('cee22bc3fe', $purgers[0]['instance_id']);
    $purge_logger_config = $this->config('purge.logger_channels');
    $channels = $purge_logger_config->get('channels');
    $this->assertSame('purger_acquia_purge_cee22bc3fe', $channels[3]['id']);

    // Acquia CMS provides special configuration for the Entity Clone module in
    // order to allow users to explicitly clone the layout canvas field (which,
    // with this configuration, is the default action) if the entity being
    // cloned has it. This way, the cloned entity will have its own instance of
    // the layout canvas. This is needed because layout canvas fields are an
    // "interesting" implementation of entity references and the default entity
    // clone behavior will cause unintentional data loss. The configuration we
    // are shipping implements Cohesion's documented best practice.
    // @see https://support.cohesiondx.com/5.4/user-guide/entity-clone-module
    $cohesion_layout_clone_settings = $this->config('entity_clone.settings')
      ->get('form_settings.cohesion_layout');
    $this->assertTrue($cohesion_layout_clone_settings['default_value']);
    $this->assertTrue($cohesion_layout_clone_settings['hidden']);
    $this->assertFalse($cohesion_layout_clone_settings['disable']);
  }

  /**
   * Returns a config object by name.
   *
   * @param string $name
   *   The name of the config object to return.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The config object, read-only to discourage this test from making any
   *   changes.
   */
  private function config(string $name) : ImmutableConfig {
    return $this->container->get('config.factory')->get($name);
  }

  /**
   * Tests Categories and Tags filters in administrative content/media lists.
   */
  public function testAdminDashboardTagsCategories() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $account = $this->createUser();
    $account->addRole('content_author');
    $account->save();
    $this->drupalLogin($account);

    /** @var \Drupal\taxonomy\VocabularyInterface $categories_vocab */
    $categories_vocab = Vocabulary::load('categories');
    $category = $this->createTerm($categories_vocab, ['name' => 'Music']);

    /** @var \Drupal\taxonomy\VocabularyInterface $tags_vocab */
    $tags_vocab = Vocabulary::load('tags');
    $tag = $this->createTerm($tags_vocab, ['name' => 'Rocks']);

    // Create a node tagged with our new category, and another one tagged with
    // our new tag, so we can test that the filters do what we expect.
    $this->createNode([
      'type' => 'page',
      'title' => 'Categories Page',
      'field_categories' => $category->id(),
      'uid' => $account->id(),
      'moderation_state' => 'published',
    ]);
    $this->createNode([
      'type' => 'page',
      'title' => 'Tags Page',
      'field_tags' => $tag->id(),
      'uid' => $account->id(),
      'moderation_state' => 'published',
    ]);

    // Visit the content overview page.
    $this->drupalGet('/admin/content');
    $assert_session->statusCodeEquals(200);

    // Try filtering by category.
    $page->selectFieldOption('Categories', $category->label());
    $page->pressButton('Filter');
    $assert_session->linkExists('Categories Page');
    $assert_session->linkNotExists('Tags Page');

    // Now try filtering by tag.
    $page->fillField('Tags', $tag->label());
    $page->selectFieldOption('Categories', '');
    $page->pressButton('Filter');
    $assert_session->linkExists('Tags Page');
    $assert_session->linkNotExists('Categories Page');

    // Create a media item tagged with our new category, and another one tagged
    // with our new tag, so we can test that the filters do what we expect.
    $this->createMedia([
      'uid' => $account->id(),
      'bundle' => 'image',
      'name' => 'Categories Media',
      'field_categories' => $category->id(),
    ]);
    $this->createMedia([
      'uid' => $account->id(),
      'bundle' => 'image',
      'name' => 'Tags Media',
      'field_tags' => $tag->id(),
    ]);

    // Visit the media overview page.
    $this->drupalGet('/admin/content/media');
    $assert_session->statusCodeEquals(200);

    // Try filtering by category.
    $page->selectFieldOption('Categories', $category->label());
    $page->pressButton('Filter');
    $assert_session->linkExists('Categories Media');
    $assert_session->linkNotExists('Tags Media');

    // Try filtering by tag.
    $page->fillField('Tags', $tag->label());
    $page->selectFieldOption('Categories', '');
    $page->pressButton('Filter');
    $assert_session->linkExists('Tags Media');
    $assert_session->linkNotExists('Categories Media');
  }

  /**
   * {@inheritdoc}
   */
  private function createMedia(array $values = []) {
    $media = $this->traitCreateMedia($values);
    $this->markEntityForCleanup($media);
    return $media;
  }

  /**
   * Test entity clone feature for new content type.
   *
   * Check if newly created content type's content can be
   * cloned by user or not.
   */
  public function testEntityCloneForNewContentType() {
    // Create new content type.
    $test_node_type = NodeType::create([
      'type' => 'test_node',
      'name' => 'Test node type',
    ]);
    $test_node_type->save();
    // Mark test entity for clean up at the end.
    $this->markEntityForCleanup($test_node_type);

    $assert_session = $this->assertSession();
    $account = $this->createUser();
    $account->addRole('content_administrator');
    $account->save();
    $this->drupalLogin($account);

    // Create a node of test_node type.
    $node_page = $this->createNode([
      'type' => 'test_node',
      'title' => 'Categories Page',
      'uid' => $account->id(),
      'moderation_state' => 'published',
    ]);

    // Visit node edit page created above.
    $this->drupalGet($node_page->toUrl('edit-form'));
    $assert_session->statusCodeEquals(200);

    // Assert clone tab exists.
    $assert_session->linkExists('Clone');
  }

  /**
   * Tests tour permission for user roles.
   *
   * - User roles with permission 'access acquia cms tour' should able to
   *   access tour page.
   * - User roles without permission 'access acquia cms tour'
   *   should not be able to access tour page.
   */
  public function testTourPermissions() {
    $assert_session = $this->assertSession();

    $roles = [
      'content_author',
      'content_editor',
      'content_administrator',
    ];
    foreach ($roles as $role) {
      $account = $this->createUser();
      $account->addRole($role);
      $account->save();
      $this->drupalLogin($account);
      $this->assertTrue($account->hasPermission('access acquia cms tour'));

      // User should be able to access the toolbar and see a Tour link.
      $assert_session->elementExists('css', '#toolbar-administration')
        ->clickLink('Tour');
      // Visit the tour page.
      $this->drupalGet('/admin/tour');
      $assert_session->statusCodeEquals(200);
    }

    // User with dashboard permission shall access the dashboard pages.
    $account = $this->createUser(['access acquia cms tour dashboard']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/tour/dashboard');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('/admin/tour/dashboard/disabled');
    $assert_session->statusCodeEquals(200);

    // Regular authenticated users should not be able to access the dashboard
    // and tour page.
    $account = $this->createUser();
    $this->drupalLogin($account);
    $this->drupalGet('/admin/tour/dashboard');
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('/admin/tour/dashboard/disabled');
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('/admin/tour');
    $assert_session->statusCodeEquals(403);
  }

  /**
   * Tests security related permission for User Administrator role.
   *
   * - User with role User Administrator should be able to access the following
   *   modules' configuration pages:
   *   - seckit
   *   - shield
   *   - honeypot
   *   - captcha
   *   - recaptcha
   *   - password_policy.
   */
  public function testSecurityModulesPermissions() {
    $assert_session = $this->assertSession();

    $account = $this->createUser();
    $account->addRole('user_administrator');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/admin/config/system/seckit');
    $assert_session->statusCodeEquals(200);

    $this->drupalGet('/admin/config/content/honeypot');
    $assert_session->statusCodeEquals(200);

    $this->drupalGet('/admin/config/people/captcha');
    $assert_session->statusCodeEquals(200);

    $this->drupalGet('/admin/config/people/captcha/recaptcha');
    $assert_session->statusCodeEquals(200);

    $this->drupalGet('/admin/config/security/password-policy');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('/admin/config/security/password-policy/add');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('/admin/config/security/password-policy/reset');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Tests Acquia CMS password policy by registering a new account.
   *
   * - Passwords must contain at least 3 different types of characters:
   *   - lowercase letters
   *   - uppercase letters
   *   - digits
   *   - special characters (optional)
   * - Passwords must be at least 8 characters long.
   * - Passwords must not contain the username.
   */
  public function testPasswordPolicy() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Password length must be at least 8 characters; try to create one that is
    // only 7.
    $this->drupalGet('/user/register');
    $page->fillField('Email address', 'example@example.com');
    $page->fillField('Username', 'example123');
    $page->fillField('Password', 'Abc129!');
    $page->fillField('Confirm password', 'Abc129!');
    $page->pressButton('Create new account');
    $assert_session->pageTextContains('The password does not satisfy the password policies.');

    // Passwords must contain at least 3 types of characters; try to create one
    // without a number in it.
    $page->fillField('Password', 'AAexaample');
    $page->fillField('Confirm password', 'AAexaample');
    $page->pressButton('Create new account');
    $assert_session->pageTextContains('The password does not satisfy the password policies.');

    // Password must not contain the username.
    // @todo Validation does not work for this constraint, isue has been raised
    // Issue - https://www.drupal.org/project/password_policy/issues/3161012
    $page->fillField('Password', 'Acb#45nbcs');
    $page->fillField('Confirm password', 'Acb#45nbcs');
    $page->pressButton('Create new account');
    // Assert that a status message appears with welcome text, and no password
    // policy error.
    $assert_session->pageTextNotContains('The password does not satisfy the password policies.');
    $assert_session->pageTextContains('Thank you for applying for an account. Your account is currently pending approval by the site administrator.');
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    // Delete user created during testing password policy.
    $user = user_load_by_mail('example@example.com');
    if ($user) {
      $user->delete();
    }
    // Revert the configuration back to default, once password policy is tested.
    $this->container->get('config.factory')
      ->getEditable('user.settings')
      ->set('verify_mail', TRUE)
      ->set('register', 'visitors')
      ->save();
    parent::tearDown();
  }

}
