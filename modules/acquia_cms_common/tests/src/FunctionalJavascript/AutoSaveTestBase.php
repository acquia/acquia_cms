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

    // Prepare autosave setup.
    // Adjust the autosave form submission interval.
    $this->config('autosave_form.settings')
      ->set('interval', 20000)
      ->save();
  }

  /**
   * Test autosave feature for an existing entity.
   */
  public function testAutoSaveForExistingNodeTitle() {
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
    $assert_session->fieldExists('Title')->setValue('Test Autosubmit Data');
    $this->waitForAutosave();

    // Reload the page.
    $this->drupalGet($node->toUrl('edit-form'));
    $this->autoSaveElementCheck();
    $assert_session->fieldValueEquals('Title', 'Test Autosubmit Data');

    // Test autosubmit if user is logged out.
    $this->drupalLogout();
    $this->drupalLogin($account);
    $this->drupalGet($node->toUrl('edit-form'));
    $this->autoSaveElementCheck(FALSE);
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
   * Check Autosave elements.
   *
   * @param bool $resume
   *   Which button to click. Resume editing if true, otherwise discard.
   */
  protected function autoSaveElementCheck($resume = TRUE) {
    $assert_session = $this->assertSession();

    $resume_editing = $assert_session->waitForElementVisible('css', '.autosave-form-resume-button');
    $this->assertNotEmpty($resume_editing);

    $discard = $assert_session->waitForElementVisible('css', '.autosave-form-reject-button');
    $this->assertNotEmpty($discard);

    // Press resume editing button, or discard.
    ($resume) ? $resume_editing->press() : $discard->press();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityUrl() {
    return "node/add/{$this->nodeType}";
  }

}
