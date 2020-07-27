<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Base class for testing generic functionality of a specific content type.
 */
abstract class ContentTypeTestBase extends ContentModelTestBase {

  use AssertMailTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['content_translation'];

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

    // Ensure that all fields in this content type are translatable.
    $this->assertConfigurableFieldsAreTranslatable('node', $this->nodeType);
  }

  /**
   * Tests access to the content type for various user roles.
   */
  public function testAccess() {
    // Since we're just testing access, make configurable fields optional so
    // we don't have to fill them out.
    $this->makeRequiredFieldsOptional();

    $this->doTestAuthorAccess();
    $this->doTestEditorAccess();
    $this->doTestAdministratorAccess();
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
  protected function doTestAuthorAccess() {
    $account = $this->drupalCreateUser();
    $account->addRole('content_author');
    $account->save();
    $this->drupalLogin($account);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet("/node/add/$this->nodeType");
    $assert_session->statusCodeEquals(200);
    // We should be able to select the language of the node.
    $assert_session->selectExists('Language');
    $page->fillField('title[0][value]', 'Pastafazoul!');
    // We should not be able to access the scheduler option.
    $assert_session->fieldNotExists('publish_on[0][value][date]');
    $assert_session->fieldNotExists('publish_on[0][value][time]');

    $assert_session->fieldNotExists('unpublish_on[0][value][date]');
    $assert_session->fieldNotExists('unpublish_on[0][value][time]');
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
  protected function doTestEditorAccess() {
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
    // We should be able to access the scheduler option.
    $assert_session->fieldExists('publish_on[0][value][date]');
    $assert_session->fieldExists('publish_on[0][value][time]');

    $assert_session->fieldExists('unpublish_on[0][value][date]');
    $assert_session->fieldExists('unpublish_on[0][value][time]');

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
  protected function doTestAdministratorAccess() {
    $account = $this->drupalCreateUser();
    $account->addRole('content_administrator');
    $account->save();
    $this->drupalLogin($account);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Test that we can create content.
    $this->drupalGet("/node/add/$this->nodeType");
    $assert_session->statusCodeEquals(200);
    // We should be able to select the language of the node.
    $assert_session->selectExists('Language');
    $page->fillField('title[0][value]', 'Pastafazoul!');
    $page->pressButton('Save');
    $assert_session->statusCodeEquals(200);

    // Test that we can edit our own content.
    $this->drupalGet('/node/4/edit');
    $assert_session->statusCodeEquals(200);
    // We should be able to access the scheduler option.
    $assert_session->fieldExists('publish_on[0][value][date]');
    $assert_session->fieldExists('publish_on[0][value][time]');

    $assert_session->fieldExists('unpublish_on[0][value][date]');
    $assert_session->fieldExists('unpublish_on[0][value][time]');

    // Test that we can edit others' content and send it through various
    // workflow states.
    Node::load(1)->set('moderation_state', 'draft')->save();
    $this->doMultipleModerationStateChanges(1, [
      'In review',
      'Published',
      'Draft',
      'Published',
      'Archived',
      'Draft',
    ]);

    // Test that we can delete our own content.
    $this->drupalGet('/node/4/delete');
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

  /**
   * Sets the default value of the content type's image field.
   *
   * Because this test class does not support JavaScript, it's not possible for
   * us to attach images to our content using the core media library. To get
   * around that, this method creates a media item for a randomly generated
   * image and sets it as the default value for the image field of the content
   * type under test (e.g., field_page_image).
   *
   * @return string
   *   The absolute URL of the randomly generated default image.
   */
  protected function getImageUrl(): string {
    $field = FieldConfig::loadByName('node', $this->nodeType, 'field_' . $this->nodeType . '_image');
    $this->assertInstanceOf(FieldConfig::class, $field);

    $uri = uniqid('public://') . '.png';
    $uri = $this->getRandomGenerator()->image($uri, '16x16', '16x16');

    /** @var \Drupal\file\FileInterface $file */
    $file = File::create([
      'uri' => $uri,
    ]);
    $file->save();

    $media = Media::create([
      'bundle' => 'image',
      'image' => $file->id(),
    ]);
    $media->save();

    $field->setDefaultValue($media->id())->save();

    return file_create_url($file->getFileUri());
  }

  /**
   * Makes all required fields of the content type under test optional.
   *
   * This is needed for testing access to the content type's add and edit forms.
   * In those cases, we're just testing access; we don't actually care about
   * data integrity, but we still need to interact with the forms and save
   * entities via the UI.
   *
   * Subclasses should take implement their own methods for testing that the
   * required fields actually behave the way we expect them to.
   */
  private function makeRequiredFieldsOptional() {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('field_config');

    $fields = $storage->loadByProperties([
      'entity_type' => 'node',
      'bundle' => $this->nodeType,
      'required' => TRUE,
    ]);
    /** @var \Drupal\field\Entity\FieldConfig $field */
    foreach ($fields as $field) {
      $field->setRequired(FALSE);
      $storage->save($field);
    }
  }

  /**
   * Asserts that the fields of the node form are in the correct order.
   *
   * @param string[] $expected_order
   *   The machine names of the fields we expect to be in the node type's form
   *   display, in the order we expect them to have.
   */
  protected function assertFieldsOrder(array $expected_order) {
    $display = $this->container->get('entity_display.repository')
      ->getFormDisplay('node', $this->nodeType);

    $this->assertDisplayComponentsOrder($display, $expected_order, "The fields of the '$this->nodeType' content type's edit form were not in the expected order.");
  }

}
