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
    // Create some published and unpublished nodes to assert search
    // functionality properly.
    foreach ($node_types as $type) {
      $type_id = $type->id();
      $type_label = $type->label();

      $term_vocab = $music = $rock = NULL;
      // As we don't have any page type vocab.
      if ($type_id != 'page') {
        /** @var \Drupal\taxonomy\VocabularyInterface $term_vocab */
        $term_vocab = Vocabulary::load($type_id . '_type');
        $music = $this->createTerm($term_vocab, ['name' => $type_label . ' Music']);
        $rock = $this->createTerm($term_vocab, ['name' => $type_label . ' Rocks']);

        $published_node = $this->drupalCreateNode([
          'type' => $type_id,
          'title' => 'Test published ' . $type->label(),
          'field_' . $type_id . '_type' => $music->id(),
          'moderation_state' => 'published',
        ]);
        $published_node->setPublished()->save();
        $unpublished_node = $this->drupalCreateNode([
          'type' => $type_id,
          'title' => 'Test unpublished ' . $type->label(),
          'field_' . $type_id . '_type' => $rock->id(),
        ]);
        $unpublished_node->setUnpublished()->save();
      }
      else {
        $published_node = $this->drupalCreateNode([
          'type' => $type_id,
          'title' => 'Test published ' . $type->label(),
          'moderation_state' => 'published',
        ]);
        $published_node->setPublished()->save();
        $unpublished_node = $this->drupalCreateNode([
          'type' => $type_id,
          'title' => 'Test unpublished ' . $type->label(),
        ]);
        $unpublished_node->setUnpublished()->save();
      }
    }

    // Visit the seach page.
    $this->drupalGet('/search');
    $assert_session->statusCodeEquals(200);
    $page->fillField('Keywords', 'Test');
    $page->pressButton('Search');

    foreach ($node_types as $type) {
      $type_label = $type->label();
      // Check if only published nodes are visible.
      $assert_session->linkExists('Test published ' . $type_label);
      // Check if unpublished nodes are not visible.
      $assert_session->linkNotExists('Test unpublished ' . $type_label);
    }
    // Check if facets filter the content as expected.
    foreach ($node_types as $type) {
      $type_label = $type->label();
      $page->clickLink($type_label . ' (1)');
      // Check if the selected content type from facets is shown.
      $assert_session->linkExists('Test published ' . $type_label);
      $assert_session->linkNotExists('Test unpublished ' . $type_label);

      if ($type->id() != 'page') {
        // Check if term facet is working properly.
        $assert_session->linkExists($type_label . ' Music (1)');
        $assert_session->linkNotExists($type_label . ' Rocks (1)');
      }
    }
  }

}
