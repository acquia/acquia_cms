<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Behat\Mink\Element\ElementInterface;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\acquia_cms\Traits\CohesionTestTrait;
use Drupal\Tests\acquia_cms_common\Traits\AssertLinksTrait;
use Drupal\Tests\acquia_cms_common\Traits\SetBackendAvailabilityTrait;
use Drupal\views\Entity\View;
use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase;

/**
 * Tests the search functionality that ships with Acquia CMS.
 *
 * @group acquia_cms_search
 * @group acquia_cms
 */
class SearchTest extends ExistingSiteSelenium2DriverTestBase {

  use CohesionTestTrait;
  use AssertLinksTrait;
  use SetBackendAvailabilityTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
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
    // @todo Delete this line once ACMS-445 is fixed.
    unset($node_types['page']);

    $this->drupalGet('/search');
    $page->fillField('keywords', 'Test');
    $page->pressButton('Search');

    // Get the container which holds the facets, and assert that, initially,
    // the content type facet is visible but none of the dependent facets are.
    $facets = $this->assertSession()->elementExists('css', '.facets-column');
    $this->assertTrue($this->assertLinkExists('Content Type', $facets)->isVisible());
    $this->assertFalse($this->assertLinkExists('Article Type', $facets)->isVisible());
    $this->assertFalse($this->assertLinkExists('Event Type', $facets)->isVisible());
    $this->assertFalse($this->assertLinkExists('Person Type', $facets)->isVisible());
    $this->assertFalse($this->assertLinkExists('Place Type', $facets)->isVisible());

    // Facets should filter the content type and "type" taxonomy as expected,
    // and we should only see published content.
    foreach ($node_types as $node_type_id => $type) {
      // Clear all selected facets.
      $this->drupalGet('/search');

      $node_type_label = $type->label();

      $page->fillField('keywords', 'Test published ' . $node_type_label);
      $page->pressButton('Search');
      // Assert that the search by title shows the proper result.
      $this->assertLinkExistsByTitle('Test published ' . $node_type_label);
      $this->assertLinkNotExistsByTitle('Test unpublished ' . $node_type_label);

      // Activate the facet for this content type.
      $this->assertLinkExists($node_type_label . ' (1)', $facets)->click();

      $this->assertLinkExistsByTitle('Test published ' . $node_type_label);
      $this->assertLinkNotExistsByTitle('Test unpublished ' . $node_type_label);

      // Pages have no facets.
      if ($node_type_id !== 'page') {
        // Open the accordion item for the "type" taxonomy of this content type.
        // @todo This is commented out because, at the moment, the facets are
        // expanded by default. If we change them to be collapsed by default, we
        // can uncomment this line.
        // $this->assertLinkExists("$node_type_label Type", $facets)->click();
        // Check if term facet is working properly.
        $page->clickLink($node_type_label . ' Music (1)');
        // Assert that the clear filter is present.
        $assert_session->linkExists('Clear filter(s)');
        // Check if node of the selected term is shown.
        $this->assertLinkExistsByTitle('Test published ' . $node_type_label);
        $this->assertLinkNotExistsByTitle('Test unpublished ' . $node_type_label);
        $assert_session->linkNotExists($node_type_label . ' Rocks (1)');
      }
    }
  }

  /**
   * Tests autocomplete search functionality.
   */
  public function testAutocomplete() {
    $page = $this->getSession()->getPage();
    $node_types = NodeType::loadMultiple();
    // @todo Delete this line once ACMS-445 is fixed.
    unset($node_types['page']);
    foreach ($node_types as $type) {
      $node_type_label = $type->label();
      $this->drupalGet('/search');
      $page->fillField('keywords', $node_type_label);

      // By default autocomplete dropdown does not appears after
      // filling field value so, let's trigger keydown event to open it.
      $this->getSession()->executeScript("jQuery('#edit-keywords--2').trigger('keydown')");
      $autocomplete_results = $this->assertSession()->waitForElementVisible('css', '.search-api-autocomplete-search');
      $this->assertNotEmpty($autocomplete_results);

      $published_title = 'Test published ' . $node_type_label;
      $unpublished_title = 'Test unpublished ' . $node_type_label;

      // Assert that autocomplete dropdown contains the title
      // of published node of the particular node type.
      $this->assertSession()->elementExists('css', 'span:contains("' . $published_title . '")', $autocomplete_results);

      // Assert that autocomplete dropdown does not contains
      // the title of unpublished node of the particular node type.
      $this->assertSession()->elementNotExists('css', 'span:contains("' . $unpublished_title . '")', $autocomplete_results);
    }

  }

  /**
   * Asserts that a link exists.
   *
   * @param string $title
   *   The title, text, or rel of the link.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   (optional) The element that contains the link.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The link element.
   */
  private function assertLinkExists(string $title, ElementInterface $container = NULL) : ElementInterface {
    return $this->assertSession()->elementExists('named', ['link', $title], $container);
  }

  /**
   * Tests that the listing page displays a fallback view if needed.
   */
  public function testFallback() {
    // Simulate an unavailable search backend, which is the only condition under
    // which we display the fallback view.
    $this->setBackendAvailability(FALSE);

    $account = $this->createUser();
    $account->addRole('content_administrator');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/search');

    // Get the container which holds the facets, and assert that, initially, the
    // content type facet is not visible but none of the dependent facets are.
    $facets = $this->assertSession()->elementExists('css', '.facets-column');
    $this->assertFalse($this->assertLinkExists('Content Type', $facets)->isVisible());
    $this->assertFalse($this->assertLinkExists('Article Type', $facets)->isVisible());
    $this->assertFalse($this->assertLinkExists('Event Type', $facets)->isVisible());
    $this->assertFalse($this->assertLinkExists('Person Type', $facets)->isVisible());
    $this->assertFalse($this->assertLinkExists('Place Type', $facets)->isVisible());

    $this->assertLinksExistInOrder();
  }

  /**
   * Returns the view entity for the listing page.
   *
   * @return \Drupal\views\Entity\View
   *   The listing page's view.
   */
  protected function getView() : View {
    return View::load('search');
  }

  /**
   * {@inheritdoc}
   */
  protected function getLinks() : array {
    $links = $this->getSession()
      ->getPage()
      ->findAll('css', 'a[title]');

    $map = function (ElementInterface $link) {
      // Our template for node teasers doesn't actually link the title -- which
      // is probably an accessibility no-no, but let's not get into that now --
      // but it does include a 'title' attribute in the "read more" link which
      // contains the actual title of the linked node.
      return $link->getAttribute('title');
    };
    return array_map($map, $links);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedLinks() : array {
    return [
      'Test published Article',
      'Test published Event',
      'Test published Page',
      'Test published Person',
      'Test published Place',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->setBackendAvailability(TRUE);
    parent::tearDown();
  }

}
