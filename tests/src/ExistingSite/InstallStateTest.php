<?php

namespace Drupal\Tests\acquia_cms\ExistingSite;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Tests\acquia_cms_common\Traits\MediaTestTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests config values that are set at install.
 *
 * @group acquia_cms
 */
class InstallStateTest extends ExistingSiteBase {

  use TaxonomyTestTrait;
  use MediaTestTrait;

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
   * Tests categories and tags filter accessibility.
   *
   * - Content and Media view pages should have the categories and tags filter.
   */
  public function testViewFilters() {
    // Preparing session and required objects.
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();
    $account = $this->drupalCreateUser();
    $account->addRole('content_author');
    $account->save();
    $this->drupalLogin($account);
    $page = $session->getPage();

    /** @var \Drupal\taxonomy\VocabularyInterface $categories */
    $categories = Vocabulary::load('categories');
    $this->createTerm($categories, ['name' => 'Music']);
    /** @var \Drupal\taxonomy\VocabularyInterface $tags */
    $tags = Vocabulary::load('tags');
    $this->createTerm($tags, ['name' => 'Rocks']);
    // Node with category.
    $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Categories Page',
      'field_categories' => [
        '0' => [
          'target_id' => 1,
        ],
      ],
      'uid' => $account->id(),
      'moderation_state' => 'published',
    ]);
    // Node with tag.
    $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Tags Page',
      'field_tags' => [
        '0' => [
          'target_id' => 2,
        ],
      ],
      'uid' => $account->id(),
      'moderation_state' => 'published',
    ]);
    // Should be able to access the content and media overview page.
    $this->drupalGet('/admin/content');
    $assert_session->statusCodeEquals(200);
    // Assert to check if categories field exists.
    $group = $assert_session->elementExists('css', '#views-exposed-form-content-page-1');
    $assert_session->fieldExists('Categories', $group);
    $assert_session->fieldExists('Tags', $group);
    // Filtering on the basis of category.
    $page->fillField('Categories', 'Music');
    $page->pressButton('Filter');
    // Check if the category node is filtered.
    $assert_session->linkExists('Categories Page');
    $assert_session->linkNotExists('Tags Page');
    // Filtering on the basis of tag.
    $page->fillField('Tags', 'Rocks');
    $page->fillField('Categories', '');
    $page->pressButton('Filter');
    // Check if the category node is filtered.
    $assert_session->linkExists('Tags Page');
    $assert_session->linkNotExists('Categories Page');

    // Media type node with category.
    $this->createMedia([
      'uid' => $account->id(),
      'bundle' => 'image',
      'name' => 'Categories Media',
      'field_categories' => [
        '0' => [
          'target_id' => 1,
        ],
      ],
    ]);
    // Media type node with tag.
    $this->createMedia([
      'uid' => $account->id(),
      'bundle' => 'image',
      'name' => 'Tags Media',
      'field_tags' => [
        '0' => [
          'target_id' => 2,
        ],
      ],
    ]);
    $this->drupalGet('/admin/content/media');
    $assert_session->statusCodeEquals(200);
    // Assert to check if tags field exists.
    $group = $assert_session->elementExists('css', '#views-exposed-form-media-media-page-list');
    $assert_session->fieldExists('Categories', $group);
    $assert_session->fieldExists('Tags', $group);
    // Filtering on the basis of category.
    $page->fillField('Categories', 'Music');
    $page->pressButton('Filter');
    // Check if the category node is filtered.
    $assert_session->linkExists('Categories Media');
    $assert_session->linkNotExists('Tags Media');
    // Filtering on the basis of tag.
    $page->fillField('Tags', 'Rocks');
    $page->fillField('Categories', '');
    $page->pressButton('Filter');
    // Check if the category node is filtered.
    $assert_session->linkExists('Tags Media');
    $assert_session->linkNotExists('Categories Media');

    $this->drupalLogout();
  }

}
