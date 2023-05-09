<?php

namespace Drupal\Tests\acquia_post_config_events_test\Functional;

use Drupal\Tests\BrowserTestBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * Tests the post config import events.
 */
class PostConfigImportCommandTest extends BrowserTestBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['acquia_post_config_events_test'];

  /**
   * Tests Post config import events.
   */
  public function testPostConfigImportEvents() {
    $this->drush('config:export', [], ['yes' => NULL]);
    $this->drush('config:import', [], ['yes' => NULL]);
    $events = $this->container->get('state')->get('invoked.post_events.import');
    $this->assertNotEmpty($events);
    $events = json_decode($events);
    $this->assertSame($events, [
      "Drupal\\acquia_post_config_events_test\\EventSubscriber\\TestPostConfigImport::postConfigImport",
    ]);
  }

}
