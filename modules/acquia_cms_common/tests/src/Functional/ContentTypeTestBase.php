<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Base class for testing generic functionality of a specific content type.
 */
abstract class ContentTypeTestBase extends BrowserTestBase {

  /**
   * The machine name of the content type under test.
   *
   * This should be overridden by subclasses.
   *
   * @var string
   */
  protected $nodeType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Ensure that the content type under test has been specified by a subclass.
    $this->assertNotEmpty($this->nodeType);

    // Do normal set-up and ensure that the content type actually exists.
    parent::setUp();
    $node_type = NodeType::load($this->nodeType);
    $this->assertInstanceOf(NodeType::class, $node_type);

    // Create a node of the type under test, belonging to user 1. This is to
    // test the capabilities of content editors and content administrators.
    $this->drupalCreateNode([
      'type' => $this->nodeType,
      'uid' => $this->rootUser->id(),
    ]);
  }

  /**
   * Asserts that all configurable fields for the content type are translatable.
   */
  public function testAllFieldsAreTranslatable() {
    $field_definitions = $this->container->get('entity_type.manager')
      ->getStorage('field_config')
      ->loadByProperties([
        'entity_type' => 'node',
        'bundle' => $this->nodeType,
      ]);

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    foreach ($field_definitions as $id => $field_definition) {
      $this->assertTrue($field_definition->isTranslatable(), "$id is not translatable, but it should be.");
      $this->assertTrue($field_definition->getFieldStorageDefinition()->isTranslatable(), "$id storage is not translatable, but it should be.");
    }
  }

  /**
   * Tests the content type as a content author.
   *
   * Asserts that content authors:
   * - Can create content of the type under test.
   * - Can edit their own content.
   * - Cannot edit others' content.
   * - Can delete their own content.
   * - Cannot delete others' content.
   * - Can transition their own content from draft to review.
   */
  public function testContentTypeAsAuthor() {
    $account = $this->drupalCreateUser();
    $account->addRole('content_author');
    $account->save();
    $this->drupalLogin($account);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet("/node/add/$this->nodeType");
    $assert_session->statusCodeEquals(200);
    $page->fillField('Title', 'Pastafazoul!');
    // We should be able to explicitly save this node as a draft.
    $page->selectFieldOption('Save as', 'Draft');
    $page->pressButton('Save');
    $assert_session->statusCodeEquals(200);

    $this->doMultipleModerationStateChanges(2, ['In review']);

    // Test that we cannot edit others' content.
    $this->drupalGet('/node/1/edit');
    $assert_session->statusCodeEquals(403);

    // Test we can delete our own content.
    $this->drupalGet('/node/2/delete');
    $assert_session->statusCodeEquals(200);

    // Test that we cannot delete others' content.
    $this->drupalGet('/node/1/delete');
    $assert_session->statusCodeEquals(403);
  }

  /**
   * Tests the content type as a content editor.
   *
   * Asserts that content editors:
   * - Cannot create content of the type under test.
   * - Can edit their own content.
   * - Can edit others' content.
   * - Can delete their own content.
   * - Can delete others' content.
   * - Can transition others' content between all states, except for restoring
   *   archived content.
   */
  public function testContentTypeAsEditor() {
    $account = $this->drupalCreateUser();
    $account->addRole('content_editor');
    $account->save();
    $this->drupalLogin($account);

    $node = $this->drupalCreateNode([
      'type' => $this->nodeType,
      'uid' => $account->id(),
    ]);

    $assert_session = $this->assertSession();

    // Test that we cannot create new content.
    $this->drupalGet("/node/add/$this->nodeType");
    $assert_session->statusCodeEquals(403);

    // Test that we can edit our own content.
    $this->drupalGet($node->toUrl('edit-form'));
    $assert_session->statusCodeEquals(200);

    // Test that we can edit others' content. Mark the node for review, then
    // transition between various workflow states.
    Node::load(1)->set('moderation_state', 'review')->save();
    $this->doMultipleModerationStateChanges(1, [
      'In review',
      'Published',
      'Draft',
      'In review',
      'Draft',
      'Published',
      'Archived',
    ]);

    // Test that we can delete our own content.
    $this->drupalGet($node->toUrl('delete-form'));
    $assert_session->statusCodeEquals(200);

    // Test that we can delete others' content.
    $this->drupalGet('/node/1/delete');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Tests the content type as a content administrator.
   *
   * Asserts that content administrators:
   * - Can create content of the type under test.
   * - Can edit their own content.
   * - Can edit others' content.
   * - Can delete their own content.
   * - Can delete others' content.
   * - Can transition others' content between all states.
   */
  public function testContentTypeAsAdministrator() {
    $account = $this->drupalCreateUser();
    $account->addRole('content_administrator');
    $account->save();
    $this->drupalLogin($account);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Test that we can create content.
    $this->drupalGet("/node/add/$this->nodeType");
    $assert_session->statusCodeEquals(200);
    $page->fillField('Title', 'Pastafazoul!');
    $page->pressButton('Save');
    $assert_session->statusCodeEquals(200);

    // Test that we can edit our own content.
    $this->drupalGet('/node/2/edit');
    $assert_session->statusCodeEquals(200);

    // Test that we can edit others' content and send it through various
    // workflow states.
    $this->doMultipleModerationStateChanges(1, [
      'In review',
      'Published',
      'Draft',
      'Published',
      'Archived',
      'Draft',
    ]);

    // Test that we can delete our own content.
    $this->drupalGet('/node/2/delete');
    $assert_session->statusCodeEquals(200);

    // Test that we can delete others' content.
    $this->drupalGet('/node/1/delete');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Applies multiple moderation state changes to a node.
   *
   * @param int $nid
   *   The ID of the node.
   * @param string[] $to_states
   *   The human readable labels of the moderation states to apply to the node,
   *   in the order that they should be applied. After each state is applied,
   *   this method will assert that the node is still visible to the current
   *   user.
   */
  protected function doMultipleModerationStateChanges(int $nid, array $to_states) {
    $assert_session = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();

    while ($to_states) {
      $to_state = array_shift($to_states);

      $this->drupalGet("/node/$nid/edit");
      $assert_session->statusCodeEquals(200);
      $page->selectFieldOption('Change to', $to_state);
      $page->pressButton('Save');
      $this->assertSame(200, $session->getStatusCode(), "Expected the node to be accessible after transitioning to $to_state.");
    }
  }

}
