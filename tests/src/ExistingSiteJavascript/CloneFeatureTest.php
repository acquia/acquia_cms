<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Tests entity clone feature for content entity by cloning a node.
 *
 * @group acquia_cms
 */
class CloneFeatureTest extends CohesionTestBase {

  /**
   * Tests that user is able to clone content entity and clone node.
   *
   * Contains all fields from parent node.
   */
  public function testCloneFeature() {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/node/add/page');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $page->fillField('Title', 'Cohesion Hero component');

    // Add Hero component to the layout canvas.
    $canvas = $this->waitForElementVisible('css', '.coh-layout-canvas');
    $this->addComponent($canvas, 'Hero');
    $page->selectFieldOption('Save as', 'Published');
    $page->pressButton('Save');

    // Clone above created node.
    $this->drupalGet('/admin/content');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', '.dropbutton-widget')->clickLink('Edit');
    $page->clickLink('Clone');
    $page->pressButton('Clone');

    // Visit admin content page to edit clone node and check
    // it contains all value from main node.
    $this->drupalGet('/admin/content');
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', '.dropbutton-widget')->clickLink('Edit');
    $assert_session->fieldValueEquals('Title', 'Cohesion Hero component - Cloned');
    // @TODO: Find a way to check components available on the clone node edit page.
  }

}
