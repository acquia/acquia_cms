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
  protected static $modules = [
    'content_translation',
    'scheduler',
    'entity_clone',
    'workbench_email',
  ];

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
  protected function setUp(): void {
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
    // Asserts that node author is receiving emails when moderation state is
    // changed to draft.
    $this->assertCount(1, $this->getMails([
      'id' => 'workbench_email_template::back_to_draft',
      'to' => $this->rootUser->getEmail(),
    ]));

    // Ensure that all fields in this content type are translatable.
    $this->assertConfigurableFieldsAreTranslatable('node', $this->nodeType);
  }

  /**
   * Tests the add/edit form of the content type.
   */
  abstract protected function doTestEditForm() : void;

  /**
   * Tests the access restrictions and add/edit form of the content type.
   */
  public function testContentType() {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $field_storage */
    $field_storage = $this->container->get('entity_type.manager')
      ->getStorage('field_config');

    // While testing access, make configurable fields optional so we don't have
    // to fill them out in the UI.
    $required_fields = $field_storage->loadByProperties([
      'entity_type' => 'node',
      'bundle' => $this->nodeType,
      'required' => TRUE,
    ]);
    /** @var \Drupal\field\Entity\FieldConfig $required_field */
    foreach ($required_fields as $required_field) {
      $required_field->setRequired(FALSE);
      $field_storage->save($required_field);
    }

    $this->doTestAuthorAccess();
    $this->doTestEditorAccess();
    $this->doTestAdministratorAccess();

    // While testing the add/edit form, make the required fields behave as they
    // normally would.
    foreach ($required_fields as $required_field) {
      $required_field->setRequired(TRUE);
      $field_storage->save($required_field);
    }
    $this->doTestEditForm();
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
   * - Can clone content entity.
   */
  protected function doTestAuthorAccess() {
    $account = $this->drupalCreateUser();
    $account->addRole('content_author');
    $account->setEmail('content_author@testing.com');
    $account->save();
    $this->drupalLogin($account);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet("/node/add/$this->nodeType");
    $assert_session->statusCodeEquals(200);
    // We should be able to select the language of the node.
    $assert_session->selectExists('Language');
    $page->fillField('title[0][value]', 'Pastafazoul!');
    // Asserts that content author cannot schedule nodes.
    $assert_session->fieldNotExists('publish_on[0][value][date]');
    $assert_session->fieldNotExists('publish_on[0][value][time]');

    $assert_session->fieldNotExists('unpublish_on[0][value][date]');
    $assert_session->fieldNotExists('unpublish_on[0][value][time]');
    // We should be able to explicitly save this node as a draft.
    $page->selectFieldOption('Save as', 'Draft');
    $page->pressButton('Save');
    $assert_session->statusCodeEquals(200);
    // Asserts that node author is receiving emails when moderation state is
    // changed to draft.
    $this->assertCount(1, $this->getMails([
      'id' => 'workbench_email_template::back_to_draft',
      'to' => 'content_author@testing.com',
    ]));

    $this->doMultipleModerationStateChanges(2, ['In review']);
    // As we don't have any content editor and content administrator, so no
    // email will be triggered.
    $this->assertCount(0, $this->getMails(['id' => 'workbench_email_template::transition_to_review']));

    // Test that we cannot edit others' content.
    $this->drupalGet('/node/1/edit');
    $assert_session->statusCodeEquals(403);

    // Test we can delete our own content.
    $this->drupalGet('/node/2/delete');
    $assert_session->statusCodeEquals(200);

    // Test that we cannot delete others' content.
    $this->drupalGet('/node/1/delete');
    $assert_session->statusCodeEquals(403);

    // Test that we can clone content.
    $this->drupalGet('/entity_clone/node/2');
    $assert_session->statusCodeEquals(200);
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
   * - Can clone content entity.
   */
  protected function doTestEditorAccess() {
    $account = $this->drupalCreateUser();
    $account->addRole('content_editor');
    $account->setEmail('content_editor@testing.com');
    $account->save();
    $this->drupalLogin($account);

    $node = $this->drupalCreateNode([
      'type' => $this->nodeType,
      'uid' => $account->id(),
    ]);
    // As the newly created node is without any state, so default one will be
    // draft and 'node author' will receive the mail.
    $this->assertCount(1, $this->getMails([
      'id' => 'workbench_email_template::back_to_draft',
      'to' => 'content_editor@testing.com',
    ]));

    $assert_session = $this->assertSession();

    // Test that we cannot create new content.
    $this->drupalGet("/node/add/$this->nodeType");
    $assert_session->statusCodeEquals(403);

    // Test that we can edit our own content.
    $this->drupalGet($node->toUrl('edit-form'));
    $assert_session->statusCodeEquals(200);
    // Test that we are able to access the scheduler option.
    $assert_session->fieldExists('publish_on[0][value][date]');
    $assert_session->fieldExists('publish_on[0][value][time]');

    $assert_session->fieldExists('unpublish_on[0][value][date]');
    $assert_session->fieldExists('unpublish_on[0][value][time]');

    // Test that we can edit others' content. Mark the node for review, then
    // transition between various workflow states.
    Node::load(1)->set('moderation_state', 'review')->save();
    // Assert that content editor receives an email when content is moved to
    // review state.
    $this->assertCount(1, $this->getMails([
      'id' => 'workbench_email_template::transition_to_review',
      'to' => 'content_editor@testing.com',
    ]));
    $this->doMultipleModerationStateChanges(1, [
      'In review',
      'Published',
      'Draft',
      'In review',
      'Draft',
      'Published',
      'Archived',
    ]);
    // As the state of node is changed to 'In review' only once, which results
    // into the total count of mails to 2 as we only have content editor
    // till now.
    $this->assertCount(2, $this->getMails([
      'id' => 'workbench_email_template::transition_to_review',
      'to' => 'content_editor@testing.com',
    ]));
    // Node has been published twice. So node author will receive two emails.
    $this->assertCount(2, $this->getMails([
      'id' => 'workbench_email_template::to_published',
      'to' => $this->rootUser->getEmail(),
    ]));
    // Node has been transitioned to draft twice. Node Author would receive
    // two emails. We already have 1 draft email notifications thus making it 3.
    $this->assertCount(3, $this->getMails([
      'id' => 'workbench_email_template::back_to_draft',
      'to' => $this->rootUser->getEmail(),
    ]));
    // Node has been archived Once. Node author will receive the email.
    $this->assertCount(1, $this->getMails([
      'id' => 'workbench_email_template::to_archived',
      'to' => $this->rootUser->getEmail(),
    ]));

    // Test that we can delete our own content.
    $this->drupalGet($node->toUrl('delete-form'));
    $assert_session->statusCodeEquals(200);

    // Test that we can delete others' content.
    $this->drupalGet('/node/1/delete');
    $assert_session->statusCodeEquals(200);

    // Test that we can clone content.
    $this->drupalGet('/entity_clone/node/1');
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
   * - Can clone content entity.
   */
  protected function doTestAdministratorAccess() {
    $account = $this->drupalCreateUser();
    $account->addRole('content_administrator');
    $account->setEmail('content_administrator@testing.com');
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
    // As the newly created node is without any state, so default one will be
    // draft and 'node author' will receive the mail.
    $this->assertCount(1, $this->getMails([
      'id' => 'workbench_email_template::back_to_draft',
      'to' => 'content_administrator@testing.com',
    ]));

    // Test that we can edit our own content.
    $this->drupalGet('/node/4/edit');
    $assert_session->statusCodeEquals(200);
    // Test that we are be able to access the scheduler option.
    $assert_session->fieldExists('publish_on[0][value][date]');
    $assert_session->fieldExists('publish_on[0][value][time]');

    $assert_session->fieldExists('unpublish_on[0][value][date]');
    $assert_session->fieldExists('unpublish_on[0][value][time]');

    // Test that we can edit others' content and send it through various
    // workflow states.
    Node::load(1)->set('moderation_state', 'draft')->save();
    // State change to draft will trigger the mail to node author.
    $this->assertCount(4, $this->getMails([
      'id' => 'workbench_email_template::back_to_draft',
      'to' => $this->rootUser->getEmail(),
    ]));
    $this->doMultipleModerationStateChanges(1, [
      'In review',
      'Published',
      'Draft',
      'Published',
      'Archived',
      'Draft',
    ]);
    // Node has been transitioned to review once. Both content editors, and
    // administrator would receive one email each.
    $this->assertCount(1, $this->getMails([
      'id' => 'workbench_email_template::transition_to_review',
      'to' => 'content_administrator@testing.com',
    ]));
    // 'Content editor' is  already having 2 email notification thus making
    // it 3.
    $this->assertCount(3, $this->getMails([
      'id' => 'workbench_email_template::transition_to_review',
      'to' => 'content_editor@testing.com',
    ]));
    // Node has been published twice. Node author would receive two email. We
    // already have 2 published email notification thus making it 4.
    $this->assertCount(4, $this->getMails([
      'id' => 'workbench_email_template::to_published',
      'to' => $this->rootUser->getEmail(),
    ]));
    // Node has been transitioned to draft twice. Node Author would receive
    // two emails. We already have 4 draft email notifications thus making it 6.
    $this->assertCount(6, $this->getMails([
      'id' => 'workbench_email_template::back_to_draft',
      'to' => $this->rootUser->getEmail(),
    ]));
    // Node has been archived Once. Node author would receive one email. We
    // already have 1 archived email notification thus making it 2.
    $this->assertCount(2, $this->getMails([
      'id' => 'workbench_email_template::to_archived',
      'to' => $this->rootUser->getEmail(),
    ]));

    // Test that we can delete our own content.
    $this->drupalGet('/node/4/delete');
    $assert_session->statusCodeEquals(200);

    // Test that we can delete others' content.
    $this->drupalGet('/node/1/delete');
    $assert_session->statusCodeEquals(200);

    // Test that we can clone content.
    $this->drupalGet('/entity_clone/node/4');
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

    return $file->createFileUrl(FALSE);
  }

  /**
   * Asserts that the fields of the node form are in the correct order.
   *
   * @param string[] $expected_order
   *   The machine names of the fields we expect to be in the node type's form
   *   display, in the order we expect them to have.
   */
  protected function assertFieldsOrder(array $expected_order) {
    $components = $this->container->get('entity_display.repository')
      ->getFormDisplay('node', $this->nodeType)
      ->getComponents();

    $this->assertDisplayComponentsOrder($components, $expected_order, "The fields of the '$this->nodeType' content type's edit form were not in the expected order.");
  }

}
