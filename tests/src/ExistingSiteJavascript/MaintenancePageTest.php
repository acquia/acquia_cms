<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase;

/**
 * Tests maintenance page as per ACMS new design.
 *
 * @group acquia_cms
 * @group low_risk
 * @group pr
 * @group push
 */
class MaintenancePageTest extends ExistingSiteSelenium2DriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->container->get('state')->set('system.maintenance_mode', TRUE);
  }

  /**
   * Tests maintenance page design.
   */
  public function testMaintenancePage() {
    $this->drupalGet('/node');
    $assert_session = $this->assertSession();
    $banner_container = $assert_session->elementExists('css', '.banner-title-img > img');
    $this->assertSame('/profiles/contrib/acquia_cms/acquia_cms.png', $banner_container->getAttribute('src'));
    $assert_session->pageTextContains("Site under maintenance");
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->container->get('state')->set('system.maintenance_mode', FALSE);
    parent::tearDown();
  }

}
