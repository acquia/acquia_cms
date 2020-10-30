<?php

namespace Drupal\Tests\acquia_cms_event\Unit;

use Drupal\acquia_cms_event\DefaultContentEventUpdate;
use Drupal\Tests\UnitTestCase;

/**
 * Simple test to ensure that asserts pass.
 *
 * @group acquia_cms_event
 * @group acquia_cms
 */
class DefaultContentUpdateEventTest extends UnitTestCase {

  protected $updateEvent;

  /**
   * Before a test method is run, setUp() is invoked.
   */
  public function setUp() {
    $this->updateEvent = new DefaultContentEventUpdate();
  }

  public function testUpdateEvent() {
    // @TODO drupalCreateNode is not working.
    $pastStartDate = $this->drupalCreateNode([
      'type' => 'event',
      'title' => 'Event Example 1',
      'field_event_start' => date('Y-m-d\TH:i:s', strtotime('-15 days')),
      'field_event_end' => date('Y-m-d\TH:i:s', strtotime('-14 days')),
      'field_door_time' => date('Y-m-d\TH:i:s', strtotime('-15 days')),
      'moderation_state' => 'published',
    ]);
    $past_event = $this->updateEvent->updateEventDates($pastStartDate);
    $this->assertEquals(date('Y-m-d', strtotime("30 days")), $past_event->get('field_event_start')->date->format('Y-m-d'));
    $this->assertEquals(date('Y-m-d', strtotime("30 days")), $past_event->get('field_event_end')->date->format('Y-m-d'));
    $this->assertEquals(date('Y-m-d', strtotime("30 days")), $past_event->get('field_door_time')->date->format('Y-m-d'));
  }

  /**
   * Once test method has finished running, tearDown() will be invoked.
   *
   * @TODO need to check weather we need this method or not.
   */
  public function tearDown() {
    unset($this->updateEvent);
  }

}
