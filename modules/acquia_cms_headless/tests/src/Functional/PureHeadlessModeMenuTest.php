<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

use Behat\Mink\Element\NodeElement;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Pure headless mode menu tests.
 *
 * @group acquia_cms
 * @group acquia_cms_headless
 * @group medium_risk
 * @group push
 */
class PureHeadlessModeMenuTest extends WebDriverTestBase {

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
    'acquia_cms_headless_ui',
    'acquia_cms_toolbar',
    'block_content',
    'media_library',
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $account = $this->drupalCreateUser();
    $account->addRole('administrator');
    $account->save();
    $this->drupalLogin($account);
    $this->drupalPlaceBlock('page_title_block', ['id' => 'page-title', 'region' => 'content', 'theme' => 'stark']);
  }

  /**
   * Checks child menu of parent.
   *
   * @param string $selector
   *   Parent menu selector.
   * @param string $parentMenuName
   *   Parent menu name.
   * @param array $children
   *   An array of child menu items.
   *
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException
   * @throws \Drupal\Core\Extension\MissingDependencyException
   *
   * @dataProvider providerMenu
   */
  public function testChildMenu(string $selector, string $parentMenuName, array $children): void {
    $this->drupalGet('/admin/headless/dashboard');
    $page = $this->getSession()->getPage();
    $menu = $page->find("css", $selector);
    $this->assertInstanceOf(NodeElement::class, $menu, "Page doesn't contain element: `$selector`.");
    $this->assertEquals($parentMenuName, $menu->getText());
    $menu->mouseOver();

    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    // Wait for menu items to visible.
    $menuItem = $assertSession->waitForElementVisible('css', '.menu-item.hover-intent');
    $this->assertInstanceOf(NodeElement::class, $menuItem);
    $childrenMenuItems = $page->findAll("css", ".toolbar-menu-administration > .toolbar-menu > .menu-item.hover-intent >  ul.toolbar-menu:first-of-type > li.menu-item");
    $this->assertCount(count($children), $childrenMenuItems);
    foreach ($childrenMenuItems as $key => $child) {
      $this->assertEquals($children[$key], $child->find("css", "a:first-child")->getText());
    }
  }

  public function testContentModelLinks() {
    // Make sure alias works fine.
    $this->drupalGet('/admin/content-models');
    $this->assertSession()->pageTextContains('Content Models');
  }

  /**
   * Data provider for ::childMenuTest().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public static function providerMenu(): array {
    return [
      [
        '.toolbar-icon-system-admin-content',
        'Content',
        [
          'Add content',
          'Blocks',
          'Files',
          'Media',
          'Block Content',
        ],
      ],
      [
        '.toolbar-icon-admin-access-control',
        'API',
        [
          'Dashboard',
          'OAuth clients',
          'OAuth settings',
          'Roles',
          'Tokens',
          'User accounts',
        ],
      ],
      [
        '.toolbar-icon-admin-content-models',
        'Data model',
        [
          'Block types',
          'Content types',
          'Media types',
          'Menus',
          'Taxonomy',
        ],
      ],
      [
        '.toolbar-icon-admin-cms',
        'System administration',
        [
          'Structure',
          'Extend',
          'Configuration',
          'People',
          'Reports',
        ],
      ],
    ];
  }

}
