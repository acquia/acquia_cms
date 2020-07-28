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

}
