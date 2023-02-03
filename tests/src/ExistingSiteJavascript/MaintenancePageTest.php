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
  protected function setUp() :void {
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
    $acquia_cms_path = $this->container->get('module_handler')->getModule('acquia_cms_common')->getPath();
    $this->assertSame('/' . $acquia_cms_path . '/acquia_cms.png', $banner_container->getAttribute('src'));
    $assert_session->pageTextContains("Site under maintenance");
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() :void {
    $this->container->get('state')->set('system.maintenance_mode', FALSE);
    parent::tearDown();
  }

}
