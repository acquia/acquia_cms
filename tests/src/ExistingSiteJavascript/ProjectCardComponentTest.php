<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Drupal\file\Entity\File;
use Drupal\Tests\acquia_cms_common\ExistingSiteJavascript\CohesionTestBase;

/**
 * Tests that Project Card Component is installed and operating correctly.
 *
 * @group acquia_cms
 */
class ProjectCardComponentTest extends CohesionTestBase {

  /**
   * Media object that need to be deleted in tearDown().
   *
   * @var object
   */
  protected $media;

  /**
   * Node object that need to be deleted in tearDown().
   *
   * @var object
   */
  protected $node;

  /**
   * Administrator account object that need to be deleted in tearDown().
   *
   * @var object
   */
  protected $adminAccount;

  /**
   * Account object.
   *
   * @var object
   */
  protected $account;

  /**
   * Array containing user accounts that need to be deleted in tearDown().
   *
   * @var array
   */
  protected $users;

  /**
   * Test that Project card component is installed.
   *
   * And used in Cohesion's layout canvas.
   */
  public function testProjectCardComponentInstall() {
    $this->adminAccount = $this->createUser();
    $this->adminAccount->addRole('administrator');
    $this->adminAccount->save();
    $this->drupalLogin($this->adminAccount);

    $this->drupalGet('/node/add/page');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $page->fillField('Title', 'Cohesion card project component');

    // Add the cohesion component in the field layout canvas.
    $canvas = $this->waitForElementVisible('css', '.coh-layout-canvas');
    $component_added = $this->addComponent($canvas, 'Card - project');
    $edit_form = $this->editComponent($component_added);
    $edit_form->pressButton('Select image');

    // Upload media of type image.
    $this->uploadMediaInComponent('image');

    $edit_form->fillField('Heading', 'Example component 123');
    $edit_form->fillField('Pre heading', 'Example');
    $edit_form->fillField('Link to page', 'https://www.acquia.com');
    $edit_form->pressButton('Apply');

    // Save the node and assign node object to the class property.
    $page = $this->waitForElementVisible('css', '.form-actions');
    $this->node = $page->pressButton('Save');
    $assert_session->pageTextContains('Page Cohesion card project component has been created.');
  }

  /**
   * Test that project card component is use/edit by site builder and developer.
   */
  public function testComponentUseEditAccess() {
    $roles = ['site_builder', 'developer'];
    foreach ($roles as $role) {
      // Create user with specific role and login.
      $this->account = $this->createUser();
      $this->users[] = $this->account;
      $this->account->addRole($role);
      $this->account->save();
      $this->drupalLogin($this->account);

      // Visit to cohesion components page.
      $this->drupalGet('/admin/cohesion/components/components');
      $page = $this->getSession()->getPage();
      $assert_session = $this->assertSession();

      // Find general components category on page.
      $general_component = $page->find('css', '#edit-accordion');

      // Check weather general component accordion is open or not.
      if (!$general_component->hasAttribute('open')) {
        $assert_session->waitForElementVisible('css', '[aria-controls="edit-accordion"]')->press();
      }
      else {
        $assert_session->waitForElementVisible('css', '[aria-controls="edit-accordion"]');
      }

      // Click on 'edit' if the component exists on the page.
      $assert_session->pageTextContains('Card - project');
      $assert_session->waitForElementVisible('css', 'ul[data-drupal-selector="edit-table-project-card-operations-data"] > li.edit > a')->click();
      $assert_session->waitForElement('css', '.cohesion-component-project-card-form');

      // Save the component and check if the desired messages are present.
      $assert_session->waitForElementVisible('css', 'ul[data-drupal-selector="edit-save"] > li input[value="Save and continue"]')->click();
      $assert_session->pageTextContains('Your component styles have been updated.');
      $assert_session->pageTextContains('Saved the Component Card - project.');

      $this->drupalLogout();
    }
  }

  /**
   * Delete entities that were created during the test.
   */
  public function tearDown(): void {
    if ($this->media) {
      $fid = $this->media->get('image')->target_id;
      $file = File::load($fid);
      $file->delete();
      $this->media->delete();
    }
    if ($this->node) {
      $this->node->delete();
    }
    if ($this->adminAccount) {
      $this->adminAccount->delete();
    }
    if (!empty($this->users)) {
      foreach ($this->users as $user) {
        $user->delete();
      }
    }
  }

}
