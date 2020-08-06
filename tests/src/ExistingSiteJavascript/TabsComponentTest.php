<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Drupal\Tests\acquia_cms_common\ExistingSiteJavascript\CohesionTestBase;

/**
 * Tests Tab container horizontal and tab item components are installed.
 *
 * @group acquia_cms
 */
class TabsComponentTest extends CohesionTestBase {

  /**
   * Test that Tab container horizontal tab items component are installed and
   * can be added in layout canvas.
   */
  public function testComponent() {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');

    // Add the component to the layout canvas.
    $canvas = $this->waitForElementVisible('css', '.coh-layout-canvas');
    $this->addComponent($canvas, 'Tabs container - horizontal tabs');
    // Add the component tab item in the dropzone of
    // Tabs container - horizontal tabs container.
    $canvas = $this->waitForElementVisible('css', 'li[data-type="Tabs container - horizontal tabs"] coh-dynamic-nodes-renderer');
    $this->addComponent($canvas, 'Tab item', 'dropzone');
    // Add the component 'Text and image' in the dropzone of Tab item.
    $canvas = $this->waitForElementVisible('css', 'li[data-type="Tab item"] coh-dynamic-nodes-renderer');
    $this->addComponent($canvas, 'Text and image', 'dropzone');
  }

  /**
   * Tests that component can be edited by a specific user role.
   *
   * @param string $role
   *   The ID of the user role to test with.
   *
   * @dataProvider providerEditAccess
   */
  public function testEditAccess(string $role) {
    $account = $this->createUser();
    $account->addRole($role);
    $account->save();
    $this->drupalLogin($account);

    // Visit to cohesion components page.
    $this->drupalGet('/admin/cohesion/components/components');
    $assert_session = $this->assertSession();

    // Ensure that the group containing the component is open.
    $details = $assert_session->elementExists('css', 'details > summary:contains(Interactive components)')->getParent();
    if (!$details->hasAttribute('open')) {
      $details->find('css', 'summary')->click();
    }

    $assert_session->elementExists('css', 'tr:contains("Tabs container - horizontal tabs")', $details)
      ->clickLink('Edit');
    $this->waitForElementVisible('css', '.cohesion-component-edit-form');

    // Visit to cohesion components page.
    $this->drupalGet('/admin/cohesion/components/components');
    $assert_session->elementExists('css', 'tr:contains("Tab item")', $details)
      ->clickLink('Edit');
    $this->waitForElementVisible('css', '.cohesion-component-edit-form');
  }

  /**
   * Data provider for ::testEditAccess().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerEditAccess() {
    return [
      ['site_builder'],
      ['developer'],
    ];
  }

}
