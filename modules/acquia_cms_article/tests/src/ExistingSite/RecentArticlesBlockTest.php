<?php

namespace Drupal\Tests\acquia_cms_article\ExistingSite;

use Behat\Mink\Element\ElementInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\node\NodeInterface;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the recent articles block on page.
 *
 * @group acquia_cms
 * @group acquia_cms_article
 */
class RecentArticlesBlockTest extends ExistingSiteBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $block = $this->placeBlock('views_block:article_cards-recent_articles_block', [
      'region' => 'content',
    ]);
    $this->markEntityForCleanup($block);

    $this->createNode([
      'type' => 'article',
      'title' => 'Article Example 1',
      'moderation_state' => 'published',
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Article Example 2',
      'moderation_state' => 'published',
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Article Example 3',
      'moderation_state' => 'published',
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Article Example 4',
      'moderation_state' => 'draft',
    ]);
  }

  /**
   * Tests the recent articles block.
   */
  public function testRecentArticlesBlock() {
    $this->drupalGet('');
    $this->assertSession()->pageTextContains('Recent Articles');
    $this->assertLinksExistInOrder();
  }

  /**
   * Asserts that a set of links are on the page, in a specific order.
   *
   * @param string[] $expected_links_in_order
   *   (optoinal) The titles of the links we expect to find, in the order that
   *   we expect them to appear on the page. If not provided, this method will
   *   search for links to all published content of the type under test.
   */
  private function assertLinksExistInOrder(array $expected_links_in_order = NULL) : void {
    if ($expected_links_in_order) {
      $count = count($expected_links_in_order);
      $expected_links_in_order = array_intersect($this->getLinksInOrder(), $expected_links_in_order);
      $this->assertCount($count, $expected_links_in_order);
    }
    else {
      $expected_links_in_order = $this->getLinksInOrder();
    }
    $expected_links_in_order = array_values($expected_links_in_order);

    $actual_links = $this->getSession()
      ->getPage()
      ->findAll('css', '.view-article-cards .coh-container .coh-heading');

    $map = function (ElementInterface $link) {
      // Our template for node teasers doesn't actually link the title -- which
      // is probably an accessibility no-no, but let's not get into that now --
      // but it does include a 'title' attribute in the "read more" link which
      // contains the actual title of the linked node.
      return $link->getText();
    };
    $actual_links = array_map($map, $actual_links);
    $actual_links = array_intersect($actual_links, $expected_links_in_order);
    $actual_links = array_values($actual_links);

    $this->assertSame($actual_links, $expected_links_in_order);
  }

  /**
   * Returns the titles of all content of the type under test.
   *
   * @return string[]
   *   The titles of all published content of the type under test, in the order
   *   we would expect to see them on the listing page.
   */
  protected function getLinksInOrder() : array {
    $ids = $this->getQuery()->execute();

    /** @var \Drupal\node\NodeInterface[] $content */
    $content = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->loadMultiple($ids);

    $map = function (NodeInterface $node) {
      return $node->getTitle();
    };
    return array_map($map, $content);
  }

  /**
   * Builds a query for all published content of the type under test.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query object to find all published content and sort with
   *   created in Descending order of the type under test.
   */
  protected function getQuery() : QueryInterface {
    return $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->getQuery()
      ->condition('type', 'article')
      ->condition('status', TRUE)
      ->sort('created', 'DESC')
      ->pager(7);
  }

}
