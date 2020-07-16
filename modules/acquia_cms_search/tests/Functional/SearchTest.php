<?php

namespace Drupal\Tests\acquia_cms_search\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the search functionality that ships with Acquia CMS.
 *
 * @group acquia_cms_search
 * @group acquia_cms
 */
class SearchTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_cms_search',
    'acquia_cms_article',
    'acquia_cms_event',
    'acquia_cms_page',
    'acquia_cms_person',
    'acquia_cms_place',
    'search_api_db',
  ];

  /**
   * Disable strict config schema checks in this test.
   *
   * Cohesion has a lot of config schema errors, and until they are all fixed,
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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = View::load('search');
    $display = &$view->getDisplay('default');
    $display['display_options']['cache'] = [
      'type' => 'none',
      'options' => [],
    ];
    $view->save();
  }

  /**
   * Tests the search functionality.
   */
  public function testSearch() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $account = $this->createUser();
    $account->addRole('content_administrator');
    $account->save();
    $this->drupalLogin($account);

    $node_types = NodeType::loadMultiple();
    // Create some published and unpublished nodes to assert that the search
    // respects the published status of content.
    foreach ($node_types as $type) {
      $published_node = $this->drupalCreateNode([
        'type' => $type->id(),
        'title' => 'Test published ' . $type->label(),
        'moderation_state' => 'published',
      ]);
      $this->assertTrue($published_node->isPublished());

      $unpublished_node = $this->drupalCreateNode([
        'type' => $type->id(),
        'title' => 'Test unpublished ' . $type->label(),
        'moderation_state' => 'draft',
      ]);
      $this->assertFalse($unpublished_node->isPublished());
    }

    $this->drupalGet('/search');
    $assert_session->statusCodeEquals(200);
    $page->fillField('Keywords', 'Test');
    $page->pressButton('Search');

    foreach ($node_types as $type) {
      // Check if only published nodes are visible.
      $assert_session->linkExists('Test published ' . $type->label());
      // Check if unpublished nodes are not visible.
      $assert_session->linkNotExists('Test unpublished ' . $type->label());
    }
  }

}
