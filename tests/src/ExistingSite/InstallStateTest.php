<?php

namespace Drupal\Tests\acquia_cms\ExistingSite;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\acquia_cms_common\Traits\MediaTestTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests config values that are set at install.
 *
 * @group acquia_cms
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
      $assert_session->addressEquals('/admin/tour');
      $assert_session->statusCodeEquals(200);
    }

    // Regular authenticated users should not be able to access the tour.
    $account = $this->createUser();
    $this->drupalLogin($account);
    $this->drupalGet('/admin/tour');
    $assert_session->statusCodeEquals(403);
  }

  /**
   * Tests security related permission for User Administrator role.
   *
   * - User with role User Administrator should be able to access the following
   *   modules' configuration pages:
   * - sedkit
   * - shield
   * - honeypot
   * - captcha
   * - recaptcha
   * - password_policy.
   */
  public function testSecurityModulesPermissions() {
    $assert_session = $this->assertSession();

    $account = $this->createUser();
    $account->addRole('user_administrator');
    $account->save();
    $this->drupalLogin($account);

    // User Administrators should be able to access seckit configuration page.
    $this->drupalGet('/admin/config/system/seckit');
    $assert_session->statusCodeEquals(200);

    // User Administrators should be able to access shield configuration page.
    $this->drupalGet('/admin/config/system/shield');
    $assert_session->statusCodeEquals(200);

    // User Administrators should be able to access honeypot configuration page.
    $this->drupalGet('/admin/config/content/honeypot');
    $assert_session->statusCodeEquals(200);

    // User Administrators should be able to access captcha configuration page.
    $this->drupalGet('/admin/config/people/captcha');
    $assert_session->statusCodeEquals(200);

    // User Administrators should be able to access recaptcha
    // configuration page.
    $this->drupalGet('/admin/config/people/captcha/recaptcha');
    $assert_session->statusCodeEquals(200);

    // User Administrators should be able to access password policy
    // configuration page.
    $this->drupalGet('/admin/config/security/password-policy');
    $assert_session->statusCodeEquals(200);

    // User Administrators should be able to access password policy add
    // configuration page.
    $this->drupalGet('/admin/config/security/password-policy/add');
    $assert_session->statusCodeEquals(200);

    // User Administrators should be able to access password reset page.
    $this->drupalGet('/admin/config/security/password-policy/reset');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Tests Acquia CMS password policy by registrering a new account.
   *
   * - Password must contain at least 3 types of characters from the following
   *   character types:
   *   - lowercase letters,
   *   - uppercase letters,
   *   - digits,
   *   - special characters (can be used but optional).
   * - Password length must be at least 8 characters.
   * - Password must not contain the user's username.
   */
  public function testPasswordPolicy() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Password length must be at least 8 characters.
    // Asserting password with 7 characters.".
    $this->drupalGet('/user/register');
    $page->fillField('Email address', 'example@example.com');
    $page->fillField('Username', 'example123');
    $page->fillField('Password', 'Abc129!');
    $page->fillField('Confirm password', 'Abc129!');
    $page->pressButton('Create new account');
    $assert_session->pageTextContains('The password does not satisfy the password policies.');

    // Password must contain at least 3 types of characters,
    // Asserting a password with missing number.
    $page->fillField('Password', 'AAexaample');
    $page->fillField('Confirm password', 'AAexaample');
    $page->pressButton('Create new account');
    // Assert that a status message appears with error that the password does
    // not satisfy the password policies.
    $assert_session->pageTextContains('The password does not satisfy the password policies.');

    // Password must not contain the user's username.
    // @TODO: Validation does not work for this constraint, need to check if
    // patch available for the same.
    // Assert valid password for sucessful account creation.
    $page->fillField('Password', 'Acb#45nbcs');
    $page->fillField('Confirm password', 'Acb#45nbcs');
    $page->pressButton('Create new account');
    // Assert that a status message appears with welcome text & does not have
    // password policy error.
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
