<?php

namespace Drupal\Tests\acquia_cms_search\ExistingSite;

use Behat\Mink\Element\ElementInterface;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\views\Entity\View;
use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase;

/**
 * Tests the search functionality that ships with Acquia CMS.
 *
 * @group acquia_cms_search
 * @group acquia_cms
 */
class SearchTest extends ExistingSiteSelenium2DriverTestBase {

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
    'facets',
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

      /** @var \Drupal\taxonomy\VocabularyInterface $term_vocab */
      $term_vocab = Vocabulary::load($node_type_id . '_type');
      if ($term_vocab) {
        // Creating couple of terms from each vocab type for published and
        // unpublished nodes.
        $music = $this->createTerm($term_vocab, ['name' => $node_type_label . ' Music']);
        $rock = $this->createTerm($term_vocab, ['name' => $node_type_label . ' Rocks']);

        $published_node_values['field_' . $node_type_id . '_type'] = $music->id();
        $unpublished_node_values['field_' . $node_type_id . '_type'] = $rock->id();
      }

      $published_node = $this->createNode($published_node_values + [
        'type' => $node_type_id,
        'title' => 'Test published ' . $node_type_label,
        'moderation_state' => 'published',
      ]);
      $this->assertTrue($published_node->isPublished());
      $unpublished_node = $this->createNode($unpublished_node_values + [
        'type' => $node_type_id,
        'title' => 'Test unpublished ' . $node_type_label,
        'moderation_state' => 'draft',
      ]);
      $this->assertFalse($unpublished_node->isPublished());
    }

    $this->drupalGet('/search');
    $page->fillField('keywords', 'Test');
    $page->pressButton('Search');
    // Check if only Content Type Accordion is shown.
    $element = $this->assertSession()->elementExists('css', '.coh-accordion-tabs-content-wrapper');
    $this->assertTrue($this->assertLinkExists('Content Type', $element)->isVisible());
    // Initially the dependent facets should not appear on the page.
    $this->assertFalse($this->assertLinkExists('Article Type', $element)->isVisible());
    $this->assertFalse($this->assertLinkExists('Event Type', $element)->isVisible());
    $this->assertFalse($this->assertLinkExists('Person Type', $element)->isVisible());
    $this->assertFalse($this->assertLinkExists('Place Type', $element)->isVisible());
    // Check if all the published nodes are visible and unpublished are not.
    foreach ($node_types as $type) {
      $node_type_label = $type->label();

      $this->assertLinkExistsByTitle('Test published ' . $node_type_label);
      $this->assertLinkNotExistsByTitle('Test unpublished ' . $node_type_label);
    }
    // Check if facets filter the content type and term type is working
    // as expected.
    foreach ($node_types as $type) {
      $node_type_label = $type->label();
      $node_type_id = $type->id();
      // Check if the selected content type from facets is shown.
      $content_type_element = $this->assertLinkExists($node_type_label . ' (1)', $element);
      $this->assertTrue($content_type_element->isVisible());
      $content_type_element->click();

      $this->assertLinkExistsByTitle('Test published ' . $node_type_label);
      $this->assertLinkNotExistsByTitle('Test unpublished ' . $node_type_label);

      if ($node_type_id !== 'page') {
        // Open the Node Type Accordion.
        $content_type_element = $this->assertLinkExists($node_type_label . ' Type', $element);
        $this->assertTrue($content_type_element->isVisible());
        $content_type_element->click();
        // Check if term facet is working properly.
        $page->clickLink($node_type_label . ' Music (1)');
        // Check if node of the selected term is shown.
        $this->assertLinkExistsByTitle('Test published ' . $node_type_label);
        $this->assertLinkNotExistsByTitle('Test unpublished ' . $node_type_label);
        $assert_session->linkNotExists($node_type_label . ' Rocks (1)');
      }
      // Going back to the initial state to check the other content type and
      // term facets.
      $this->drupalGet('/search');
    }
  }

  /**
   * Assert that the link exists with the given title attribute.
   *
   * @param string $title
   *   The title of the node.
   */
  private function assertLinkExistsByTitle(string $title) : void {
    $this->assertSession()->elementExists('css', 'a.coh-link[title="' . $title . '"]');
  }

  /**
   * Assert that the link exists inside the accordion container.
   *
   * @param string $title
   *   The title of the link field.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   The accordion container element.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The element that has the link.
   */
  private function assertLinkExists(string $title, ElementInterface $container = NULL) : ElementInterface {
    return $this->assertSession()->elementExists('named', ['link', $title], $container);
  }

  /**
   * Assert that the link doesn't exists with the given title attribute.
   *
   * @param string $title
   *   The title of the node.
   */
  private function assertLinkNotExistsByTitle(string $title) : void {
    $this->assertSession()->elementNotExists('css', 'a.coh-link[title="' . $title . '"]');
  }

}
