<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Tests "Background image container" component.
 *
 * @group acquia_cms
 */
class BackgroundImageContainerTest extends CohesionTestBase {

  /**
   * Tests that the component can be added to a layout canvas.
   */
  public function testComponent() {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    // Create a random image that we can select in the media library when
    // editing the component.
    $this->createMedia(['bundle' => 'image']);

    $this->drupalGet('/node/add/page');
    $page = $this->getSession()->getPage();

    $page->fillField('Title', 'Test Background image container');

    // Add the component to the layout canvas.
    $canvas = $this->waitForElementVisible('css', '.coh-layout-canvas');
    $component_added = $this->addComponent($canvas, 'Background image container');
    $edit_form = $this->editComponent($component_added);
    $this->openMediaLibrary($edit_form, 'Select image');
    $this->selectMedia(0);
    $this->insertSelectedMedia();
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
    $details = $assert_session->elementExists('css', 'details > summary:contains(Layout components)')->getParent();
    if (!$details->hasAttribute('open')) {
      $details->find('css', 'summary')->click();
    }

    $assert_session->elementExists('css', 'tr:contains("Background image container")', $details)
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
