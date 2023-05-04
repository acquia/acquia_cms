<?php

namespace Drupal\Tests\acquia_post_config_events_test\Functional;

use Drupal\Tests\BrowserTestBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * Tests the post config export events.
 */
class PostConfigExportCommandTest extends BrowserTestBase {

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
   * Tests Post config export events.
   */
  public function testPostConfigExportEvents() {
    $this->drush('config:export', [], ['yes' => NULL]);
    $events = $this->container->get('state')->get('invoked.post_events.export');
    $this->assertNotEmpty($events);
    $events = json_decode($events);
    $this->assertSame($events, [
      "Drupal\\acquia_post_config_events_test\\EventSubscriber\\TestAcquiaPostConfigExport::postConfigExport",
      "Drupal\\acquia_post_config_events_test\\EventSubscriber\\TestPostConfigExport::postConfigExport",
    ]);
  }

  /**
   * Tests Post config export events on Acquia Cloud.
   */
  public function testPostConfigExportEventsOnAcquiaCloud() {
    $this->drush('config:export', [], ['yes' => NULL], NULL, NULL, 0, NULL, [
      "AH_SITE_ENVIRONMENT" => "dev",
      "AH_APPLICATION_UUID" => $this->container->get('uuid')->generate(),
    ]);
    $events = $this->container->get('state')->get('invoked.post_events.export');
    $this->assertNotEmpty($events);
    $events = json_decode($events);
    $this->assertSame($events, [
      "Drupal\\acquia_post_config_events_test\\EventSubscriber\\TestAcquiaPostConfigExport::postConfigExport",
    ]);
  }

}
