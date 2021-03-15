<?php

namespace Drupal\Tests\acquia_cms_event\ExistingSite;

use Behat\Mink\Element\ElementInterface;
use Drupal\Tests\acquia_cms_common\Traits\AssertLinksTrait;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the upcoming events block on page.
 *
 * @group acquia_cms
 * @group acquia_cms_event
 * @group medium_risk
 * @group push
 */
class UpcomingEventsBlockTest extends ExistingSiteBase {

  use AssertLinksTrait;
  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $block = $this->placeBlock('views_block:event_cards-upcoming_events_block', [
      'region' => 'content',
      'id' => 'upcoming_events_block',
    ]);
    $this->markEntityForCleanup($block);

    // To show event start/end date always in future.
    $future_year = date('Y', strtotime('+10 year'));

    $this->createNode([
      'type' => 'event',
      'title' => 'Event Example 1',
      'field_event_start' => $future_year . '-10-03T22:00:00',
      'field_event_end' => $future_year . '-10-09T12:00:00',
      'moderation_state' => 'published',
    ]);
    $this->createNode([
      'type' => 'event',
      'title' => 'Event Example 2',
      'field_event_start' => $future_year . '-09-13T22:00:00',
      'field_event_end' => $future_year . '-09-16T12:00:00',
      'moderation_state' => 'published',
    ]);
    $this->createNode([
      'type' => 'event',
      'title' => 'Event Example 3',
      'field_event_start' => $future_year . '-09-03T22:00:00',
      'field_event_end' => $future_year . '-09-03T12:00:00',
      'moderation_state' => 'published',
    ]);
    $this->createNode([
      'type' => 'event',
      'title' => 'Event Example 4',
      'field_event_start' => $future_year . '-10-03T22:00:00',
      'field_event_end' => $future_year . '-10-03T12:00:00',
      'moderation_state' => 'draft',
    ]);
  }

  /**
   * Tests the upcoming event block.
   */
  public function testUpcomingEventsBlock() {
    $this->drupalGet('');
    $this->assertLinksExistInOrder();
  }

  /**
   * {@inheritdoc}
   */
  protected function getLinks() : array {
    $links = $this->getSession()
      ->getPage()
      ->findAll('css', '#block-upcoming-events-block .view-event-cards .coh-container .coh-heading');

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
      'Event Example 3',
      'Event Example 2',
      'Event Example 1',
    ];
  }

}
