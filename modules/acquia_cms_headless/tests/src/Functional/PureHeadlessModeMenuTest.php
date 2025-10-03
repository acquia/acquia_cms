<?php

namespace Drupal\Tests\acquia_cms_headless\Functional;

use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Behat\Mink\Element\NodeElement;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ModuleExtensionList;
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
    'acquia_cms_headless_ui',
  ];

  /**
   * Disable strict config schema checks in this test.
   *
   * Scheduler has a config schema errors, and until it's fixed,
   * this test cannot pass unless we disable strict config schema checking
   * altogether. Since strict config schema isn't critically important in
   * testing this functionality, it's okay to disable it for now, but it should
   * be re-enabled (i.e., this property should be removed) as soon as possible.
   *
   * @var bool
   */
  // @codingStandardsIgnoreStart
  protected $strictConfigSchema = FALSE;
  // @codingStandardsIgnoreEnd

  /**
   * The module installer object.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * The module installer object.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

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
    $this->moduleInstaller = $this->container->get('module_installer');
    $this->moduleList = $this->container->get('extension.list.module');
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
    if ($this->installModule('acquia_cms_toolbar') && $this->installModule('block_content')) {

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
  }

  /**
   * Test content model links.
   */
  public function testContentModelLinks() {
    // Make sure alias works fine.
    $this->drupalGet('/admin/content-models');
    $this->assertSession()->pageTextContains('Content Models');
  }

  /**
   * Checks if given module exist and tries to enable it.
   *
   * @param string $module
   *   Given module machine_name.
   *
   * @return bool
   *   Returns true|false based on module exist and on successful installation.
   *
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException
   * @throws \Drupal\Core\Extension\MissingDependencyException
   */
  protected function installModule(string $module): bool {
    try {
      if ($this->moduleList instanceof ModuleExtensionList) {
        $this->moduleList->get($module);
      }
    }
    catch (UnknownExtensionException $e) {
      return FALSE;
    }
    return $this->moduleInstaller->install([$module]);
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
          'Scheduled Content',
          'Add content',
          'Blocks',
          'Files',
          'Media',
          'Scheduled Media',
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
