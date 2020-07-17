<?php

namespace Drupal\Tests\acquia_cms_search\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\views\Entity\View;

/**
 * Tests the search functionality that ships with Acquia CMS.
 *
 * @group acquia_cms_search
 * @group acquia_cms
 */
class SearchTest extends BrowserTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'cohesion_theme';

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
      $node_type_id = $type->id();
      $node_type_label = $type->label();

      $term_vocab = $music = $rock = NULL;
      // As we don't have any page type vocab.
      if ($node_type_id != 'page') {
        /** @var \Drupal\taxonomy\VocabularyInterface $term_vocab */
        $term_vocab = Vocabulary::load($node_type_id . '_type');
        $music = $this->createTerm($term_vocab, ['name' => $node_type_label . ' Music']);
        $rock = $this->createTerm($term_vocab, ['name' => $node_type_label . ' Rocks']);

        $published_node = $this->drupalCreateNode([
          'type' => $node_type_id,
          'title' => 'Test published ' . $type->label(),
          'field_' . $node_type_id . '_type' => $music->id(),
          'moderation_state' => 'published',
        ]);
        $this->assertTrue($published_node->isPublished());
        $unpublished_node = $this->drupalCreateNode([
          'type' => $node_type_id,
          'title' => 'Test unpublished ' . $type->label(),
          'field_' . $node_type_id . '_type' => $rock->id(),
        ]);
        $this->assertFalse($unpublished_node->isPublished());
      }
      else {
        $published_node = $this->drupalCreateNode([
          'type' => $node_type_id,
          'title' => 'Test published ' . $type->label(),
          'moderation_state' => 'published',
        ]);
        $this->assertTrue($published_node->isPublished());
        $unpublished_node = $this->drupalCreateNode([
          'type' => $node_type_id,
          'title' => 'Test unpublished ' . $type->label(),
        ]);
        $this->assertFalse($unpublished_node->isPublished());
      }
    }

    $this->drupalGet('/search');
    $assert_session->statusCodeEquals(200);
    $page->fillField('Keywords', 'Test');
    $page->pressButton('Search');
    // Check if all the published nodes are visible and unpushished are not.
    foreach ($node_types as $type) {
      $node_type_label = $type->label();

      $assert_session->linkExists('Test published ' . $node_type_label);
      $assert_session->linkNotExists('Test unpublished ' . $node_type_label);
    }
    // Check if facets filter the content type and term type is working
    // as expected.
    foreach ($node_types as $type) {
      $node_type_label = $type->label();
      $page->clickLink($node_type_label . ' (1)');
      // Check if the selected content type from facets is shown.
      $assert_session->linkExists('Test published ' . $node_type_label);
      $assert_session->linkNotExists('Test unpublished ' . $node_type_label);

      if ($type->id() != 'page') {
        // Check if term facet is working properly.
        $page->clickLink($node_type_label . ' Music (1)');
        // Check if node of the selected term is shown.
        $assert_session->linkExists('Test published ' . $node_type_label);
        $assert_session->linkNotExists('Test unpublished ' . $node_type_label);
        $assert_session->linkNotExists($node_type_label . ' Rocks (1)');
      }
      // Going back to the initial state to check the other content type and
      // term facets.
      $this->drupalGet('/search');
      $page->fillField('Keywords', 'Test');
      $page->pressButton('Search');
    }
  }

}
