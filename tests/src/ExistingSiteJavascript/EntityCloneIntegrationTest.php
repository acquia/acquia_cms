<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Drupal\node\Entity\Node;

/**
 * Tests Cohesion's integration with Entity Clone.
 *
 * @group acquia_cms
 * @group site_studio
 */
class EntityCloneIntegrationTest extends CohesionComponentTestBase {

  /**
   * Tests that a user is able to clone a node and its components.
   */
  public function testClone() {
    $account = $this->createUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    // Create a node programmatically so it will be automatically cleaned up at
    // the end of this test.
    $node = $this->createNode(['type' => 'page']);
    $edit_form = $node->toUrl('edit-form');

    $this->drupalGet($edit_form);
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Add Hero component to the layout canvas.
    $this->getLayoutCanvas()->add('Hero');
    $this->pressSaveButton();

    // Clone the node in the UI.
    $this->drupalGet($edit_form);
    $page->clickLink('Clone');
    $page->pressButton('Clone');

    $expected_message = sprintf('The entity %s (%d) of type node was cloned.', $node->getTitle(), $node->id());
    $assert_session->pageTextContains($expected_message);

    // Ensure that the clone is cleaned up automatically at the end of the test.
    $clone = Node::load($node->id() + 1);
    $this->assertInstanceOf(Node::class, $clone);
    $this->markEntityForCleanup($clone);

    $this->drupalGet($clone->toUrl('edit-form'));
    $assert_session->fieldValueEquals('Title', $node->getTitle() . ' - cloned');
    // Assert that the Cohesion components in the source node were cloned
    // as well.
    $this->getLayoutCanvas()->assertComponent('Hero');
  }

}
