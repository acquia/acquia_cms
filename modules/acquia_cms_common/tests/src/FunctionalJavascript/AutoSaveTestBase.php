<?php

namespace Drupal\Tests\acquia_cms_common\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests the Autosave configuration shipped with Acquia CMS.
 */
abstract class AutoSaveTestBase extends WebDriverTestBase {

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

    // Adjust the autosave form submission interval.
    $this->config('autosave_form.settings')
      ->set('interval', 20000)
      ->save();
  }

  /**
   * Test autosave feature for an existing entity.
   */
  public function testAutoSaveForExistingNode() {
    $account = $this->drupalCreateUser();
    $account->addRole('content_author');
    $account->save();
    $this->drupalLogin($account);
    $assert_session = $this->assertSession();

    // Test a node edit form.
    $node = $this->drupalCreateNode([
      'type' => $this->nodeType,
      'uid' => $account->id(),
      'title' => 'Test Default Data',
    ]);

    $this->drupalGet($node->toUrl('edit-form'));
    $this->waitForAutosave();
    $assert_session->fieldExists('Title')->setValue('Test Autosave Data');
    $this->waitForAutosave();

    // Reload the page.
    $this->getSession()->reload();
    $this->restoreAutoSavedChanges();
    $assert_session->fieldValueEquals('Title', 'Test Autosave Data');

    // Test autosave if user is logged out.
    $this->drupalLogout();
    $this->drupalLogin($account);
    $this->drupalGet($node->toUrl('edit-form'));
    $this->discardAutoSaveChanges();
    $assert_session->fieldValueEquals('Title', 'Test Default Data');
  }

  /**
   * Waits for the current form to be autosaved.
   */
  private function waitForAutosave() {
    $element = $this->assertSession()
      ->elementExists('css', '#autosave-notification');

    $is_visible = $element->waitFor(20, function (NodeElement $element) {
      return $element->isVisible();
    });
    $this->assertTrue($is_visible);

    $is_hidden = $element->waitFor(10, function (NodeElement $element) {
      return $element->isVisible() === FALSE;
    });
    $this->assertTrue($is_hidden);
  }

  /**
   * Check for Resume Editing button and click on it if found.
   */
  protected function restoreAutoSavedChanges() {
    $resume_editing = $this->assertSession()->waitForElementVisible('named', ['button', 'Resume editing']);
    $this->assertNotEmpty($resume_editing);
    $resume_editing->press();
  }

  /**
   * Check for Discard button and click on it if found.
   */
  protected function discardAutoSaveChanges() {
    $discard = $this->assertSession()->waitForElementVisible('named', ['button', 'Discard']);
    $this->assertNotEmpty($discard);
    $discard->press();
  }

}
