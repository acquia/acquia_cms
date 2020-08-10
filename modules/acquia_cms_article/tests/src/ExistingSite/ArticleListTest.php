<?php

namespace Drupal\Tests\acquia_cms_article\ExistingSite;

use Behat\Mink\Element\ElementInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\views\Entity\View;
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

    $time = time();

    $this->createNode([
      'type' => 'article',
      'title' => 'The secret article',
      'created' => $time++,
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Alpha',
      'moderation_state' => 'published',
      'field_categories' => $categories[0],
      'field_article_type' => $types[0],
      'created' => $time++,
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Beta',
      'moderation_state' => 'published',
      'field_categories' => $categories[1],
      'field_article_type' => $types[1],
      'created' => $time++,
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Charlie',
      'moderation_state' => 'published',
      'field_categories' => $categories[2],
      'field_article_type' => $types[2],
      'created' => $time++,
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Delta',
      'moderation_state' => 'published',
      'field_categories' => $categories[3],
      'field_article_type' => $types[0],
      'created' => $time++,
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Echo',
      'moderation_state' => 'published',
      'field_categories' => $categories[0],
      'field_article_type' => $types[1],
      'created' => $time++,
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Foxtrot',
      'moderation_state' => 'published',
      'field_categories' => $categories[1],
      'field_article_type' => $types[2],
      'created' => $time++,
    ]);
  }

  /**
   * Toggle Fallback view.
   *
   * @param bool $status
   *   Flag to use fallback view or not.
   */
  public function toggleFallback(bool $status) {
    $view = View::load('articles');
    $display = &$view->getDisplay('default');
    $display['display_options']['cache'] = [
      'type' => 'none',
      'options' => [],
    ];
    $display['display_options']['empty']['view_fallback']['simulate_unavailable'] = $status;
    $view->save();
  }

  /**
   * Tests the "all articles" listing page.
   */
  public function testListPage() {
    $this->drupalGet('/articles');

    $assert_session = $this->assertSession();
    // Assert that all categories facets are available.
    $assert_session->linkExists('Music (2)');
    $assert_session->linkExists('Art (2)');
    $assert_session->linkExists('Literature (1)');
    $assert_session->linkExists('Math (1)');

    // Assert all article type facets are available.
    $assert_session->linkExists('Blog Post (2)');
    $assert_session->linkExists('Press Release (2)');
    $assert_session->linkExists('News (2)');

    // All articles should be visible except for the secret one.
    $this->assertLinksExistInOrder([
      'Foxtrot',
      'Echo',
      'Delta',
      'Charlie',
      'Beta',
      'Alpha',
    ]);
    $assert_session->linkNotExists('The secret article');

    // Filter by a category and ensure that the expected articles are visible.
    $page = $this->getSession()->getPage();
    $page->clickLink('Art (2)');
    $assert_session->addressEquals('/articles/category/art');
    $this->assertLinksExistInOrder(['Foxtrot', 'Beta']);
    $assert_session->linkNotExists('Alpha');
    $assert_session->linkNotExists('Charlie');
    $assert_session->linkNotExists('Delta');
    $assert_session->linkNotExists('Echo');
    $assert_session->linkNotExists('The secret article');

    // The choice of a category should narrow down the results in the type
    // facet.
    $assert_session->linkNotExists('Blog Post');
    $assert_session->linkExists('Press Release (1)');
    $assert_session->linkExists('News (1)');

    // Filtering by type should narrow the results down even more.
    $page->clickLink('News (1)');
    $assert_session->addressEquals('/articles/type/news/category/art');
    $assert_session->linkNotExists('Alpha');
    $assert_session->linkNotExists('Beta');
    $assert_session->linkNotExists('Charlie');
    $assert_session->linkNotExists('Delta');
    $assert_session->linkNotExists('Echo');
    $assert_session->linkExists('Foxtrot');
    $assert_session->linkNotExists('The secret article');

    // Removing a facet should widen the results.
    $page->clickLink('Art (1)');
    $assert_session->addressEquals('/articles/type/news');
    $this->assertLinksExistInOrder(['Foxtrot', 'Charlie']);
    $assert_session->linkNotExists('Alpha');
    $assert_session->linkNotExists('Beta');
    $assert_session->linkNotExists('Delta');
    $assert_session->linkNotExists('Echo');
    $assert_session->linkNotExists('The secret article');
  }

  /**
   * Tests the "all articles" listing page for fallback view.
   */
  public function testFallback() {
    // Make sure that fallback view is enabled.
    $this->toggleFallback(TRUE);

    $this->drupalGet('/articles');
    $assert_session = $this->assertSession();

    // Assert that all categories facets are un-available.
    $assert_session->linkNotExists('Music (2)');
    $assert_session->linkNotExists('Art (2)');
    $assert_session->linkNotExists('Literature (1)');
    $assert_session->linkNotExists('Math (1)');

    // Assert all article type facets are un-available.
    $assert_session->linkNotExists('Blog Post (2)');
    $assert_session->linkNotExists('Press Release (2)');
    $assert_session->linkNotExists('News (2)');

    // All articles should be visible except for the secret one.
    $this->assertLinksExistInOrder([
      'Foxtrot',
      'Echo',
      'Delta',
      'Charlie',
      'Beta',
      'Alpha',
    ]);
    $assert_session->linkNotExists('The secret article');
  }

  /**
   * Asserts that a set of links are on the page, in a specific order.
   *
   * @param string[] $expected_links_in_order
   *   The titles of the links we expect to find, in the order that we expect
   *   them to appear on the page.
   */
  private function assertLinksExistInOrder(array $expected_links_in_order) : void {
    $actual_links = $this->getSession()
      ->getPage()
      ->findAll('css', 'a[title]');

    $map = function (ElementInterface $link) {
      // Our template for node teasers doesn't actually link the title -- which
      // is probably an accessibility no-no, but let's not get into that now --
      // but it does include a 'title' attribute in the "read more" link which
      // contains the actual title of the linked node.
      return $link->getAttribute('title');
    };
    $actual_links = array_map($map, $actual_links);
    $actual_links = array_intersect($actual_links, $expected_links_in_order);
    $actual_links = array_values($actual_links);

    $this->assertSame($actual_links, $expected_links_in_order);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->toggleFallback(FALSE);
    parent::tearDown();
  }

}
