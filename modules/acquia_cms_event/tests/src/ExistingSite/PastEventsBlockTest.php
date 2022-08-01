<?php

namespace Drupal\Tests\acquia_cms_event\ExistingSite;

use Behat\Mink\Element\ElementInterface;
use Drupal\Tests\acquia_cms_common\Traits\AssertLinksTrait;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the past events block on page.
 *
 * @group acquia_cms
 * @group acquia_cms_event
 * @group medium_risk
 * @group push
 */
class PastEventsBlockTest extends ExistingSiteBase {

  use AssertLinksTrait;
  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $block = $this->placeBlock('views_block:event_cards-past_events_block', [
      'region' => 'content',
      'id' => 'past_events_block',
    ]);
    $this->markEntityForCleanup($block);

    $this->createNode([
      'type' => 'event',
      'title' => 'Event Example 1',
      'field_event_start' => '2020-06-03T22:00:00',
      'field_event_end' => '2020-06-09T12:00:00',
      'moderation_state' => 'published',
    ]);
    $this->createNode([
      'type' => 'event',
      'title' => 'Event Example 2',
      'field_event_start' => '2020-07-13T22:00:00',
      'field_event_end' => '2020-07-16T12:00:00',
      'moderation_state' => 'published',
    ]);
    $this->createNode([
      'type' => 'event',
      'title' => 'Event Example 3',
      'field_event_start' => '2020-07-03T22:00:00',
      'field_event_end' => '2020-07-03T12:00:00',
      'moderation_state' => 'published',
    ]);
    $this->createNode([
      'type' => 'event',
      'title' => 'Event Example 4',
      'field_event_start' => '2020-08-03T22:00:00',
      'field_event_end' => '2020-08-03T12:00:00',
      'moderation_state' => 'draft',
    ]);
  }

  /**
   * Tests the past event block.
   */
  public function testPastEventsBlock() {
    $this->drupalGet('');
    $this->assertLinksExistInOrder();
  }

  /**
   * {@inheritdoc}
   */
  protected function getLinks(): array {
    $links = $this->getSession()
      ->getPage()
      ->findAll('css', '#block-past-events-block article a');

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
      'Event Example 2',
      'Event Example 3',
      'Event Example 1',
    ];
  }

}
