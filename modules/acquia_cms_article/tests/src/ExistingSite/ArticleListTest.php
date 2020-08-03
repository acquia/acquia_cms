<?php

namespace Drupal\Tests\acquia_cms_article\ExistingSite;

use Drupal\taxonomy\Entity\Vocabulary;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the "all articles" listing page.
 *
 * @group acquia_cms_article
 */
class ArticleListTest extends ExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $vocabulary = Vocabulary::load('categories');
    $this->assertInstanceOf(Vocabulary::class, $vocabulary);
    $categories = [
      $this->createTerm($vocabulary, ['name' => 'Music'])->id(),
      $this->createTerm($vocabulary, ['name' => 'Art'])->id(),
      $this->createTerm($vocabulary, ['name' => 'Literature'])->id(),
      $this->createTerm($vocabulary, ['name' => 'Math'])->id(),
    ];

    $vocabulary = Vocabulary::load('article_type');
    $types = [
      $this->createTerm($vocabulary, ['name' => 'Blog Post'])->id(),
      $this->createTerm($vocabulary, ['name' => 'Press Release'])->id(),
      $this->createTerm($vocabulary, ['name' => 'News'])->id(),
    ];

    $this->createNode([
      'type' => 'article',
      'title' => 'The secret article',
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Alpha',
      'moderation_state' => 'published',
      'field_categories' => $categories[0],
      'field_article_type' => $types[0],
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Beta',
      'moderation_state' => 'published',
      'field_categories' => $categories[1],
      'field_article_type' => $types[1],
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Charlie',
      'moderation_state' => 'published',
      'field_categories' => $categories[2],
      'field_article_type' => $types[2],
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Delta',
      'moderation_state' => 'published',
      'field_categories' => $categories[3],
      'field_article_type' => $types[0],
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Echo',
      'moderation_state' => 'published',
      'field_categories' => $categories[0],
      'field_article_type' => $types[1],
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Foxtrot',
      'moderation_state' => 'published',
      'field_categories' => $categories[1],
      'field_article_type' => $types[2],
    ]);
  }

  /**
   * Tests the "all articles" listing page.
   */
  public function testListPage() {
    $this->drupalGet('/articles');

    $assert_session = $this->assertSession();
    // Assert that all categories facets are available.
    $assert_session->linkExists('Music');
    $assert_session->linkExists('Art');
    $assert_session->linkExists('Literature');
    $assert_session->linkExists('Math');

    // Assert all article type facets are available.
    $assert_session->linkExists('Blog Post');
    $assert_session->linkExists('Press Release');
    $assert_session->linkExists('News');

    // All articles should be visible except for the secret one.
    $assert_session->linkExists('Alpha');
    $assert_session->linkExists('Beta');
    $assert_session->linkExists('Charlie');
    $assert_session->linkExists('Delta');
    $assert_session->linkExists('Echo');
    $assert_session->linkExists('Foxtrot');
    $assert_session->linkNotExists('The secret article');

    // Filter by a category and ensure that the expected articles are visible.
    $page = $this->getSession()->getPage();
    $page->clickLink('Art');
    $assert_session->addressEquals('/articles/category/art');
    $assert_session->linkNotExists('Alpha');
    $assert_session->linkExists('Beta');
    $assert_session->linkNotExists('Charlie');
    $assert_session->linkNotExists('Delta');
    $assert_session->linkNotExists('Echo');
    $assert_session->linkExists('Foxtrot');
    $assert_session->linkNotExists('The secret article');

    // The choice of a category should narrow down the results in the type
    // facet.
    $assert_session->linkNotExists('Blog Post');
    $assert_session->linkExists('Press Release');
    $assert_session->linkExists('News');

    // Filtering by type should narrow the results down even more.
    $page->clickLink('News');
    $assert_session->addressEquals('/articles/type/news/category/art');
    $assert_session->linkNotExists('Alpha');
    $assert_session->linkNotExists('Beta');
    $assert_session->linkNotExists('Charlie');
    $assert_session->linkNotExists('Delta');
    $assert_session->linkNotExists('Echo');
    $assert_session->linkExists('Foxtrot');
    $assert_session->linkNotExists('The secret article');

    // Removing a facet should widen the results.
    $page->clickLink('Art');
    $assert_session->addressEquals('/articles/type/news');
    $assert_session->linkNotExists('Alpha');
    $assert_session->linkNotExists('Beta');
    $assert_session->linkExists('Charlie');
    $assert_session->linkNotExists('Delta');
    $assert_session->linkNotExists('Echo');
    $assert_session->linkExists('Foxtrot');
    $assert_session->linkNotExists('The secret article');
  }

}
