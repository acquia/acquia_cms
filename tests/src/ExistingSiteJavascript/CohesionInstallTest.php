<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Drupal\Tests\acquia_cms_common\ExistingSiteJavascript\CohesionTestBase;

/**
 * Tests that Cohesion is installed and operating correctly.
 *
 * @group acquia_cms
 */
abstract class CohesionInstallTest extends CohesionTestBase {

  /**
   * Tests that Cohesion's layout canvas can be used.
   */
  public function testLayoutCanvas() {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');
    $canvas = $this->waitForElementVisible('css', '.coh-layout-canvas');

    $component_added = $this->addComponent($canvas, 'Text');
    $edit_form = $this->editComponent($component_added);
    $edit_form->fillField('Component title', 'Example component');
    $edit_form->pressButton('Apply');

    $this->assertComponent($canvas, 'Example component');
  }

}
