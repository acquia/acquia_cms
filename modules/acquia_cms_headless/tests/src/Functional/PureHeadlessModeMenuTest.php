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
    if ($this->installModule('acquia_cms_toolbar')) {
      $this->drupalGet('/admin/headless/dashboard');
      $page = $this->getSession()->getPage();
      $menu = $page->find("css", $selector);
      $this->assertInstanceOf(NodeElement::class, $menu, "Page doesn't contain element: `$selector`.");
      $this->assertEquals($parentMenuName, $menu->getText());
      $menu->mouseOver();

      /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
      $assertSession = $this->assertSession();
      // Wait for menu items to visible.
      $menuItem = $assertSession->waitForElementVisible('css', '.hover-intent.menu-item');
      $this->assertInstanceOf(NodeElement::class, $menuItem);
      $childrenMenuItems = $menuItem->findAll("css", "ul.toolbar-menu li.level-2");
      $this->assertCount(count($children), $childrenMenuItems);
      foreach ($childrenMenuItems as $key => $child) {
        $this->assertEquals($children[$key], $child->find("css", ".toolbar-box > a")->getText());
      }
    }
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
  public function providerMenu(): array {
    return [
      [
        '.toolbar-icon-system-admin-content',
        'Content',
        [
          'Scheduled Content',
          'Add content',
          'Files',
          'Media',
          'Scheduled Media',
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
