<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase;

/**
 * Tests that Cohesion is installed and operating correctly.
 *
 * @group acquia_cms
 */
class CohesionInstallTest extends ExistingSiteSelenium2DriverTestBase {

  /**
   * Tests that Cohesion's layout canvas can be used.
   */
  public function testLayoutCanvas() {
    $assert_session = $this->assertSession();

    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');
    $canvas = $assert_session->waitForElementVisible('css', '.coh-layout-canvas');
    $this->assertNotEmpty($canvas);

    $add_component_button = $canvas->find('css', 'button[aria-label="Add content"]');
    $this->assertNotEmpty($add_component_button);
    $add_component_button->press();
  }

}
