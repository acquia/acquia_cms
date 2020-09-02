<?php

namespace Drupal\Tests\acquia_cms_event\ExistingSite;

use Behat\Mink\Element\ElementInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\NodeInterface;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the past events block on page.
 *
 * @group acquia_cms
 * @group acquia_cms_event
 */
class PastEventsBlockTest extends ExistingSiteBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $block = $this->placeBlock('views_block:event_cards-block_2', [
      'region' => 'content',
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
      'title' => 'Event Example 3',
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
    $this->assertSession()->pageTextContains('Past Events');
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
      ->findAll('css', '.view-event-cards .coh-container .coh-heading');

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
   *   An entity query object to find all published content with end date is
   *   less than and equal to now and sort with
   *   event end date in descending order of the type under test.
   */
  protected function getQuery() : QueryInterface {
    $current_datetime = new DrupalDateTime('now');
    $current_datetime = $current_datetime->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    return $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->getQuery()
      ->condition('type', 'event')
      ->condition('status', TRUE)
      ->condition('field_event_end', $current_datetime, '<=')
      ->sort('field_event_end', 'DESC')
      ->pager(7);
  }

}
