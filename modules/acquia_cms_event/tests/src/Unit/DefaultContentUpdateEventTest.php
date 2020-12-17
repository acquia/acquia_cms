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

  /**
   * DefaultContentEventUpdate object.
   *
   * @var \Drupal\acquia_cms_event\DefaultContentEventUpdate
   */
  protected $updateEvent;

  /**
   * Before a test method is run, setUp() is invoked.
   */
  public function setUp() {
    $this->updateEvent = new DefaultContentEventUpdate();
  }

  /**
   * Tests the UpdateEvent function to update the event date and time.
   *
   * @see Drupal\acquia_cms_event\DefaultContentEventUpdate::getUpdatedDates()
   */
  public function testUpdateEvent() {
    // Asserting when event is in past.
    $pastEvent = [
      'start_date' => date('Y-m-d', strtotime('-15 days')),
      'end_date' => date('Y-m-d', strtotime('-14 days')),
      'door_time' => date('Y-m-d', strtotime('-15 days')),
    ];
    $updated_event = $this->updateEvent->getUpdatedDates($pastEvent);
    $this->assertEquals(date('Y-m-d', strtotime("30 days")), $updated_event['start_date']);
    $this->assertEquals(date('Y-m-d', strtotime($updated_event['start_date'] . '+1 day')), $updated_event['end_date']);
    $this->assertEquals(date('Y-m-d', strtotime("30 days")), $updated_event['door_time']);
    // Asserting when event in past and door time greater than event end time.
    $pastEvent = [
      'start_date' => date('Y-m-d', strtotime('-15 days')),
      'end_date' => date('Y-m-d', strtotime('-18 days')),
      'door_time' => date('Y-m-d', strtotime('+15 days')),
    ];
    $updated_event = $this->updateEvent->getUpdatedDates($pastEvent);
    $this->assertEquals(date('Y-m-d', strtotime("30 days")), $updated_event['start_date']);
    $this->assertEquals(date('Y-m-d', strtotime($updated_event['start_date'] . '+1 day')), $updated_event['end_date']);
    $this->assertEquals(date('Y-m-d', strtotime("30 days")), $updated_event['door_time']);

    // Asserting when event is in future.
    $pastEvent = [
      'start_date' => date('Y-m-d', strtotime('+36 days')),
      'end_date' => date('Y-m-d', strtotime('+40 days')),
      'door_time' => date('Y-m-d', strtotime('+38 days')),
    ];
    $updated_event = $this->updateEvent->getUpdatedDates($pastEvent);
    $this->assertEquals(date('Y-m-d', strtotime("36 days")), $updated_event['start_date']);
    $this->assertEquals(date('Y-m-d', strtotime("40 days")), $updated_event['end_date']);
    $this->assertEquals(date('Y-m-d', strtotime("36 days")), $updated_event['door_time']);
  }

  /**
   * Once test method has finished running, tearDown() will be invoked.
   */
  public function tearDown() {
    parent::tearDown();
    unset($this->updateEvent);
  }

}
