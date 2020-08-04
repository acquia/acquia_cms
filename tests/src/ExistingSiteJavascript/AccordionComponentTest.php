<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Tests that "Accordion container and Accordion item" component is installed.
 *
 * And operating correctly.
 *
 * @group acquia_cms
 */
class AccordionComponentTest extends CohesionTestBase {

  /**
   * Tests that the component can be added to a layout canvas.
   */
  public function testComponent() {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');

    // Add the component to the layout canvas.
    $canvas = $this->waitForElementVisible('css', '.coh-layout-canvas');
    $this->addComponent($canvas, 'Accordion container');
    // Add the component in the dropzone of Accordion container.
    $canvas = $this->waitForElementVisible('css', 'li[data-type="Accordion container"] coh-dynamic-nodes-renderer');
    $this->addComponent($canvas, 'Accordion item', 'dropzone');
    // Add the component in the dropzone of Accordion item.
    $canvas = $this->waitForElementVisible('css', 'li[data-type="Accordion item"] coh-dynamic-nodes-renderer');
    $this->addComponent($canvas, 'Text', 'dropzone');
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

    $assert_session->elementExists('css', 'tr:contains("Accordion container")', $details)
      ->clickLink('Edit');
    $this->waitForElementVisible('css', '.cohesion-component-edit-form');

    // Visit to cohesion components page.
    $this->drupalGet('/admin/cohesion/components/components');
    $assert_session->elementExists('css', 'tr:contains("Accordion item")', $details)
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
