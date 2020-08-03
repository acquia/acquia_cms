<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Drupal\Tests\acquia_cms_common\ExistingSiteJavascript\CohesionTestBase;

/**
 * Tests that Project Card Component is installed and operating correctly.
 *
 * @group acquia_cms
 */
class ProjectCardComponentTest extends CohesionTestBase {

  /**
   * Test that the "Card - project" component can be added to a layout canvas.
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

    // Add the component to the layout canvas.
    $canvas = $this->waitForElementVisible('css', '.coh-layout-canvas');
    $component_added = $this->addComponent($canvas, 'Card - project');
    $edit_form = $this->editComponent($component_added);
    $this->openMediaLibrary($edit_form, 'Select image');
    $this->selectMedia(0);
    $this->insertSelectedMedia();

    $edit_form->fillField('Heading', 'Example component 123');
    $edit_form->fillField('Pre heading', 'Example');
    $edit_form->fillField('Link to page', 'https://www.acquia.com');
    $edit_form->pressButton('Apply');
  }

  /**
   * Test that project card component is use/edit by site builder and developer.
   *
   * @dataProvider roleProvider
   */
  public function testComponentUseEditAccess($role) {
    $account = $this->createUser();
    $account->addRole($role);
    $account->save();
    $this->drupalLogin($account);

    // Visit to cohesion components page.
    $this->drupalGet('/admin/cohesion/components/components');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Find general components category on page.
    $general_component = $page->find('css', '#edit-accordion');

    // Check weather general component accordion is open or not.
    if (!$general_component->hasAttribute('open')) {
      $this->waitForElementVisible('css', '[aria-controls="edit-accordion"]')->press();
    }
    else {
      $this->waitForElementVisible('css', '[aria-controls="edit-accordion"]');
    }

    // Click on 'edit' if the component exists on the page.
    $assert_session->pageTextContains('Card - project');
    $this->getSession()->executeScript("jQuery('span:contains(Card - project)').parents('tr:first').find('li.edit > a')[0].click()");
    $assert_session->waitForElement('css', '.cohesion-component-edit-form');

    // Save the component and check if the desired messages are present.
    $assert_session->buttonExists('Save and continue')->press();
    $assert_session->pageTextContains('Your component styles have been updated.');
    $assert_session->pageTextContains('Saved the Component Card - project.');
  }

  /**
   * Provide roles.
   *
   * @return array
   *   Return role.
   */
  public function roleProvider() {
    return [
      ['site_builder'],
      ['developer'],
    ];
  }

}
