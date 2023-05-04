<?php

namespace Drupal\Tests\acquia_post_config_events_test\Functional;

use Drush\TestTraits\DrushTestTrait;

/**
 * Tests the stacked kernel functionality.
 *
 * @group Routing
 */
class PostSkipSiteInstallExistingConfigTest extends ExistingSiteInstallBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    $this->settings['settings']['existing_site_status'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];
    parent::prepareEnvironment();
  }

  /**
   * Tests DO not invoke any site install from existing config events.
   */
  public function testSkipSiteInstallExistingConfigEvents() {
    $events = $this->container->get('state')->get('invoked.post_events.existing_site_install');
    $this->assertNull($events);
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigTarball(): string {
    return dirname(__FILE__, 6) . "/fixtures/testing.tar.gz";
  }

}
