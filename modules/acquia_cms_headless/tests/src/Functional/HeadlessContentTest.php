<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

/**
 * Base class for the Headless Content administrator browser tests.
 */
class HeadlessContentTest extends HeadlessContentAdminTestBase {

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
    // $this->assertNull($this->getSession()->getPage()->findLink('View'));
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
