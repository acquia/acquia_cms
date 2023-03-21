<?php

namespace Drupal\Tests\acquia_cms_post_config_events_test\Functional;

use Drush\TestTraits\DrushTestTrait;

/**
 * Tests the post Site install existing config events.
 */
class PostSiteInstallExistingConfigTest extends ExistingSiteInstallBase {

  use DrushTestTrait;

  /**
   * Tests Post site install from existing config events.
   */
  public function testSiteInstallExistingConfigEvents() {
    $events = $this->container->get('state')->get('invoked.post_events.existing_site_install');
    $this->assertNotEmpty($events);
    $events = json_decode($events);
    $this->assertSame($events, [
      "Drupal\\acquia_cms_post_config_events_test\\EventSubscriber\\TestPostSiteInstallExistingConfig::postSiteInstallExistingConfig",
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigTarball(): string {
    return dirname(__FILE__, 6) . "/fixtures/testing.tar.gz";
  }

}
