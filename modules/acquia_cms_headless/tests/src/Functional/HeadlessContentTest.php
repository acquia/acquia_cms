<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\acquia_cms_headless\Traits\HeadlessNextJsTrait;

/**
 * Base class for the Headless Content administrator browser tests.
 */
class HeadlessContentTest extends WebDriverTestBase {

  use HeadlessNextJsTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
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

    // Set up a content type.
    $this->drupalCreateContentType([
      'type' => 'test',
      'name' => 'Test',
      'third_party_settings' => [
        "acquia_cms_common" => [
          "workflow_id" => "editorial",
        ],
      ],
    ]);
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
    $this->assertTabMenus($primaryTabs, "admin/content");

    // Enable pure headless mode.
    $this->enableHeadlessMode();
    // Visit add nextJs site page.
    $this->assertNewNextJsSites();
    // Configure nextJs entity types and Validate nextJs entity config.
    $this->assertNextJsEntityTypeConfigure();
    // Create test node.
    $node = $this->drupalCreateNode([
      'type' => 'test',
      'title' => 'Headless Test Page',
      'status' => 'published',
    ]);
    $nid = $node->id();
    $this->drupalGet("admin/content");
    // Check title is not clickable.
    $this->assertNull($this->clickLink($node->getTitle()));
    // Node edit page.
    $path = "node/$nid/edit";
    $this->drupalGet($path);
    $this->assertSession()->pageTextContains('Edit Test Headless Test Page');
    $this->assertSession()->linkNotExists('View');
    $nodePageMenus = [
      'API' => '/jsonapi/node/test/' . $node->uuid(),
      'Edit' => '/node/' . $nid . '/edit',
      'Preview' => '/node/' . $nid . '/site-preview',
      'Delete' => '/node/' . $nid . '/delete',
      'Revisions' => '/node/' . $nid . '/revisions',
      'Clone' => '/entity_clone/node/' . $nid,
    ];
    $menuList = $this->cssSelect('ul.tabs--primary li');
    // Check the total count of node tabs.
    $this->assertCount(6, $menuList);
    foreach ($menuList as $menu) {
      $menuOrder[] = str_replace(' (active tab)', '', $menu->getText());
    }
    // Assertion for menu order.
    $this->assertEquals($menuOrder, array_keys($nodePageMenus));
    // Assertion test for tabs of node page.
    $this->assertTabMenus($nodePageMenus, $path);
  }

  /**
   * Content preview test.
   */
  public function testContentPreview(): void {
    $assert = $this->assertSession();
    // Visit add nextJs site page.
    $this->assertNewNextJsSites();
    // Configure nextJs entity types and Validate nextJs entity config.
    $this->assertNextJsEntityTypeConfigure();

    // Create test node.
    $node = $this->drupalCreateNode([
      'type' => 'test',
      'title' => 'Headless Test Page',
    ]);
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Visit content preview.
    $this->drupalGet('node/' . $node->id() . '/site-preview');
    $this->assertTrue($assert->optionExists('edit-site', 'headless_site_one')->isSelected());
    $assert->selectExists('edit-site')->selectOption('headless_site_two');
    $assert->buttonExists('Submit')->press();
    $this->assertTrue($assert->optionExists('edit-site', 'headless_site_two')->isSelected());
    $assert->elementExists('css', 'li.live-link a[target=_blank]');
    $assert->selectExists('edit-new-state')->selectOption('published');
    $assert->buttonExists('Apply')->press();
  }

  /**
   * Assert configure nextJs entity type.
   */
  protected function assertNextJsEntityTypeConfigure(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet("admin/config/services/next/entity-types/add");
    $assert->selectExists('id')->selectOption('node.test');
    $assert->waitForElementVisible('css', '.settings-container');
    $this->assertTrue($assert->optionExists('id', 'node.test')->isSelected());
    $assert->selectExists('site_resolver')->selectOption('site_selector');
    $assert->assertWaitOnAjaxRequest();
    $assert->waitForText('Next.js sites');
    $this->assertTrue($assert->optionExists('site_resolver', 'site_selector')->isSelected());
    $page->checkField('sites[headless_site_one]');
    $assert->checkboxChecked('sites[headless_site_one]');
    $page->checkField('sites[headless_site_two]');
    $assert->checkboxChecked('sites[headless_site_two]');
    $assert->buttonExists('Save')->press();
  }

  /**
   * Assert new nextjs site.
   */
  protected function assertNewNextJsSites() {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    // Visit add nextJs site page.
    $this->drupalGet("admin/config/services/next/sites/add");
    // Fields exists check.
    $assert->fieldExists('label');
    $assert->fieldExists('base_url');
    $assert->fieldExists('preview_url');
    $assert->fieldExists('preview_secret');
    $assert->buttonExists('Save');
    // Setup nextJS site.
    $page->fillField('Label', 'Headless Site One');
    $page->fillField('base_url', 'https://localhost.com:3000');
    $page->fillField('preview_url', 'https://localhost.com:3000/api/preview');
    $page->fillField('preview_secret', 'secret1one');
    $assert->waitForElementVisible('css', '.admin-link');
    $assert->elementExists('named', ['button', 'Save'])->click();

    // Setup another nextJs site.
    $this->drupalGet("admin/config/services/next/sites/add");
    $page->fillField('Label', 'Headless Site Two');
    $page->fillField('base_url', 'https://localhost.com:3001');
    $page->fillField('preview_url', 'https://localhost.com:3001/api/preview');
    $page->fillField('preview_secret', 'secret2two');
    $assert->waitForElementVisible('css', '.admin-link');
    $assert->elementExists('named', ['button', 'Save'])->click();
  }

  /**
   * Perfom assertions for tabs/menus.
   */
  protected function assertTabMenus(array $data, string $path): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $assert->elementExists('css', 'ul.tabs--primary ');
    foreach ($data as $name => $url) {
      $originalUrl = $page->findLink($name)->getAttribute('href');
      $this->assertEquals($url, $originalUrl);
      $page->findLink($name)->click();
      $this->drupalGet($path);
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
