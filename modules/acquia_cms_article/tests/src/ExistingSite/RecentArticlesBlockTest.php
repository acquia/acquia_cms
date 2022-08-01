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
 * @group low_risk
 * @group pr
 * @group push
 */
class RecentArticlesBlockTest extends ExistingSiteBase {

  use AssertLinksTrait;
  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $block = $this->placeBlock('views_block:article_cards-recent_articles_block', [
      'region' => 'content',
      'id' => 'recent_articles_block',
    ]);
    $this->markEntityForCleanup($block);

    $time = time();

    $this->createNode([
      'type' => 'article',
      'title' => 'Article Example 1',
      'moderation_state' => 'published',
      'created' => $time++,
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Article Example 2',
      'moderation_state' => 'published',
      'created' => $time++,
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Article Example 3',
      'moderation_state' => 'published',
      'created' => $time++,
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'Article Example 4',
      'moderation_state' => 'draft',
      'created' => $time++,
    ]);
  }

  /**
   * Tests the recent articles block.
   */
  public function testRecentArticlesBlock() {
    $this->drupalGet('');
    $this->assertLinksExistInOrder();
  }

  /**
   * {@inheritdoc}
   */
  protected function getLinks() : array {
    $links = $this->getSession()
      ->getPage()
      ->findAll('css', '#block-recent-articles-block article a');

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
