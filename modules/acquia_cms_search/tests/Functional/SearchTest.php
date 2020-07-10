<?php

namespace Drupal\Tests\acquia_cms_search\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\views\Entity\View;

/**
 * Tests search ships with Acquia CMS.
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

    // Create a node tagged with our new category, and another one tagged with
    // our new tag, so we can test that the filters do what we expect.
    $node = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Test published Page',
      'moderation_state' => 'published',
    ]);
    $node->setPublished()->save();
    $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Test unpublished Page',
    ]);
    $node->setUnpublished()->save();
    $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test published Article',
      'moderation_state' => 'published',
    ]);
    $node->setPublished()->save();
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test unpublished Article',
    ]);
    $node->setUnpublished()->save();
    $this->drupalCreateNode([
      'type' => 'event',
      'title' => 'Test published Event',
      'moderation_state' => 'published',
    ]);
    $node->setPublished()->save();
    $this->drupalCreateNode([
      'type' => 'event',
      'title' => 'Test unpublished Event',
    ]);
    $node->setUnpublished()->save();
    $this->drupalCreateNode([
      'type' => 'person',
      'title' => 'Test published Person',
      'moderation_state' => 'published',
    ]);
    $node->setPublished()->save();
    $this->drupalCreateNode([
      'type' => 'person',
      'title' => 'Test unpublished Person',
    ]);
    $node->setUnpublished()->save();
    $this->drupalCreateNode([
      'type' => 'place',
      'title' => 'Test published Place',
      'moderation_state' => 'published',
    ]);
    $node->setPublished()->save();
    $this->drupalCreateNode([
      'type' => 'place',
      'title' => 'Test unpublished Place',
    ]);
    $node->setUnpublished()->save();

    $this->drupalLogout();

    // Visit the seach page.
    $this->drupalGet('/search');
    $assert_session->statusCodeEquals(200);
    $page->fillField('Keywords', 'Test');
    $page->pressButton('Search');

    // Check if only published nodes are visible.
    $assert_session->linkExists('Test published Page');
    $assert_session->linkExists('Test published Event');
    $assert_session->linkExists('Test published Person');
    $assert_session->linkExists('Test published Place');
    $assert_session->linkExists('Test published Article');
    $assert_session->linkExists('Test published Person');
    // Check if unpublished nodes are not visible.
    $assert_session->linkNotExists('Test unpublished Page');
    $assert_session->linkNotExists('Test unpublished Event');
    $assert_session->linkNotExists('Test unpublished Person');
    $assert_session->linkNotExists('Test unpublished Place');
    $assert_session->linkNotExists('Test unpublished Article');
    $assert_session->linkNotExists('Test unpublished Person');
  }

}
