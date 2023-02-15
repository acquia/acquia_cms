<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for the Headless Content administrator browser tests.
 */
class HeadlessContentTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_headless',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // @todo Remove this check when Acquia Cloud IDEs support running functional
    // JavaScript tests.
    if (AcquiaDrupalEnvironmentDetector::isAhIdeEnv()) {
      $this->markTestSkipped('This test cannot run in an Acquia Cloud IDE.');
    }
    parent::setUp();
    $account = $this->drupalCreateUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);

    // Visit content page.
    $this->drupalGet("admin/content");
  }

  /**
   * Content admin test.
   */
  public function testContentAdmin(): void {
    // Validating the primary menu tabs on admin content page.
    $primaryTabs = [
      'Content' => '/admin/content',
      'Files' => '/admin/content/files',
      'Media' => '/admin/content/media',
    ];
    // Assertion test for tabs of content page.
    $this->assertTabMenus($primaryTabs);

    // Create test content type.
    $this->drupalCreateContentType([
      'type' => 'test',
      'name' => 'Test',
    ]);

    // Enable pure headless mode.
    $this->enableHeadlessMode();
    // Create test node.
    $node = $this->drupalCreateNode([
      'type' => 'test',
      'title' => 'Headless Test Page',
      'status' => '1',
    ]);
    $nid = $node->id();
    $this->drupalGet("admin/content");
    // Check title is not clickable.
    $this->assertNull($this->clickLink($node->getTitle()));
    // Node edit page.
    $this->drupalGet("node/$nid/edit");
    $this->assertSession()->pageTextContains('Edit Test Headless Test Page');
    // $this->assertFalse($this->clickLink('View'));
    $nodePageMenus = [
      'API' => '/jsonapi/node/test/' . $node->uuid(),
      'Edit' => '/node/' . $nid . '/edit',
      'Preview' => '/node/' . $nid . '/site-preview',
      'Delete' => '/node/' . $nid . '/delete',
      'Revisions' => '/node/' . $nid . '/revisions',
      'Clone' => '/entity_clone/node/' . $nid,
    ];
    // Assertion test for tabs of node page.
    $this->assertTabMenus($nodePageMenus);
  }

  /**
   * Perfom assertions for tabs/menus.
   */
  protected function assertTabMenus(array $data): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $assert->elementExists('css', 'ul.tabs--primary ');
    foreach ($data as $name => $url) {
      $originalUrl = $page->findLink($name)->getAttribute('href');
      $this->assertEquals($url, $originalUrl);
      // $assert->statusCodeEquals(200);
    }
  }

  /**
   * Function to enable headless mode.
   */
  protected function enableHeadlessMode(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet("admin/tour/dashboard");
    $assert->waitForElementVisible('css', '.ui-dialog .acms-welcome-modal');
    $assert->waitForText('Welcome to Acquia CMS.');
    $assert->elementExists('css', '.ui-icon-closethick')->click();
    $assert->elementExists('css', 'summary[role="button"].claro-details__summary')->click();
    $page->checkField('headless_mode');
    $assert->checkboxChecked('edit-headless-mode');
    $page->pressButton('Save');
    $this->drupalGet("admin/content");
  }

}
