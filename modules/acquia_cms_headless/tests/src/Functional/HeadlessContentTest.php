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
    $this->drupalPlaceBlock('local_tasks_block', ['id' => 'local-tasks', 'region' => 'content', 'theme' => 'stark']);
    $this->drupalPlaceBlock('page_title_block', ['id' => 'page-title', 'region' => 'content', 'theme' => 'stark']);

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
    // Enable pure headless mode.
    $this->enableHeadlessMode();
    // Visit add nextJs site page.
    $this->assertNewNextJsSites();
    // Configure nextJs entity types and Validate nextJs entity config.
    $this->assertNextJsEntityTypeConfigure();
  }

  /**
   * Content admin test.
   */
  public function testContentAdmin(): void {
    // Visit content page.
    $this->drupalGet("admin/content");

    // Validating the primary menu tabs on admin content page.
    $primaryTabs = [
      'Content' => '/admin/content',
      'Files' => '/admin/content/files',
      'Media' => '/admin/content/media',
    ];
    // Assertion test for tabs of content page.
    $this->assertTabMenus($primaryTabs, "admin/content");

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
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    $assertSession->pageTextContains('Headless Test Page');
    // @todo Below commented test is failing in 3.0-rc8 version of Gin theme.
    // Howere this was working in 3.0-rc5 will be fixed in ACMS-3456.
    /*
    $assertSession->linkNotExists('View');
    $nodePageMenus = [
    'API' => '/jsonapi/node/test/' . $node->uuid(),
    'Edit' => '/node/' . $nid . '/edit',
    'Preview' => '/node/' . $nid . '/site-preview',
    'Revisions' => '/node/' . $nid . '/revisions',
    'Clone' => '/entity_clone/node/' . $nid,
    ];
    $menuList = $this->cssSelect('ul.tabs--primary li');
    // Check the total count of node tabs.
    $this->assertCount(6, $menuList);
    $menuOrder = [];
    foreach ($menuList as $menu) {
    $tabTitle = str_replace(' (active tab)', '', $menu->getText());
    if ($tabTitle) {
    $menuOrder[] = $tabTitle;
    }
    }
    // Assertion for menu order.
    $this->assertEquals($menuOrder, array_keys($nodePageMenus));
    Assertion test for tabs of node page.
    $this->assertTabMenus($nodePageMenus, $path);
     */
    // Assert delete buton.
    $deleteButton = $this->getSession()->getPage()->findLink('Delete');
    $this->assertEquals('Delete', $deleteButton->getText());
    $this->assertEquals('/node/' . $nid . '/delete', $deleteButton->getAttribute('href'));
  }

  /**
   * Content preview test.
   */
  public function testContentPreview(): void {
    $assert = $this->assertSession();
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
    $assert->elementExists('css', '.operations li a[target=_blank]');
    $assert->selectExists('edit-new-state')->selectOption('published');
    $assert->buttonExists('Apply')->press();
  }

  /**
   * Perfom assertions for tabs/menus.
   */
  protected function assertTabMenus(array $data, string $path): void {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    $page = $this->getSession()->getPage();
    $assertSession->waitForElementVisible('css', '#block-local-tasks > ul');
    $assertSession->elementExists('css', '#block-local-tasks > ul');
    foreach ($data as $name => $url) {
      $originalUrl = $page->findLink($name)->getAttribute('href');
      $this->assertEquals($url, $originalUrl);
      $page->findLink($name)->click();
      $this->drupalGet($path);
    }
  }

}
