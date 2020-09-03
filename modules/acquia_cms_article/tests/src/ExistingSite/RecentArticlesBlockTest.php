<?php

namespace Drupal\Tests\acquia_cms_article\ExistingSite;

use Behat\Mink\Element\ElementInterface;
use Drupal\Tests\acquia_cms_common\Traits\AssertLinksTrait;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the recent articles block on page.
 *
 * @group acquia_cms
 * @group acquia_cms_article
 */
class RecentArticlesBlockTest extends ExistingSiteBase {

  use AssertLinksTrait;
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
   * {@inheritdoc}
   */
  protected function getLinks() : array {
    $links = $this->getSession()
      ->getPage()
      ->findAll('css', '.view-article-cards .coh-container .coh-heading');

    $map = function (ElementInterface $link) {
      return $link->getText();
    };
    return array_map($map, $links);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedLinks() : array {
    return [
      'Article Example 3',
      'Article Example 2',
      'Article Example 1',
    ];
  }

}
