<?php

namespace Drupal\Tests\acquia_cms_common\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Menu\AssertBreadcrumbTrait;
use Drupal\views\Views;

/**
 * Tests to verify breadcrumbs appearing on Node create/edit page.
 *
 * @group acquia_cms_common
 * @group acquia_cms
 * @group push
 */
class NodeBreadcrumbTest extends BrowserTestBase {

  use AssertBreadcrumbTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_common',
    'views',
  ];

  /**
   * The drupal user object.
   *
   * @var Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * The path to frontpage of the site.
   *
   * @var string
   */
  protected $frontPagePath;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);
    $this->adminUser = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
    ]);
    $this->frontPagePath = Url::fromRoute('<front>')->toString();
  }

  /**
   * Tests breadcrumb path on Node create page.
   */
  public function testNodeAddBreadcrumb() {
    $this->drupalLogin($this->adminUser);
    $this->assertBreadcrumb('node/add/page', [
      $this->frontPagePath => 'Home',
      'node' => 'Node',
      'node/add' => 'Add content',
    ]);
  }

  /**
   * Tests breadcrumb path on Node edit page.
   */
  public function testNodeEditBreadcrumb() {
    $this->drupalLogin($this->adminUser);
    $node = $this->drupalCreateNode([
      'type' => 'page',
      'title' => $this->t('My Page Content'),
      'uid' => $this->adminUser->id(),
    ]);
    $node->save();
    $this->assertBreadcrumb("node/" . $node->id() . "/edit", [
      $this->frontPagePath => 'Home',
      'node' => 'Node',
      $node->toUrl()->toString() => $this->t('My Page Content'),
    ]);
  }

  /**
   * Tests breadcrumb when title is updated on frontpage view.
   */
  public function testNodeBreadcrumbFromFrontpageView() {
    $this->drupalLogin($this->adminUser);
    $view = Views::getView('frontpage');
    $view->getDisplay()->display['display_options']['title'] = "Another title";
    $view->save();
    $this->assertBreadcrumb('node/add/page', [
      $this->frontPagePath => 'Home',
      'node' => 'Another title',
      'node/add' => 'Add content',
    ]);
  }

}
