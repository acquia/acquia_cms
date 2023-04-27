<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Behat\Mink\Element\ElementInterface;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\acquia_cms\Traits\AwaitTrait;
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
 * @group site_studio
 * @group low_risk
 * @group pr
 * @group push
 */
class SearchTest extends ExistingSiteSelenium2DriverTestBase {

  use AwaitTrait, CohesionTestTrait, AssertLinksTrait, SetBackendAvailabilityTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->getDriverInstance()->resizeWindow(1920, 800);
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
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSearch(): void {
    $account = $this->createUser();
    $account->addRole('content_administrator');
    $account->save();
    $this->drupalLogin($account);
    $node_types = NodeType::loadMultiple();

    $this->drupalGet('/search');
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', '.views-element-container .coh-style-search-block')->fillField('keywords', 'Test');
    $assert_session->elementExists('css', '.views-element-container input.button')->keyPress('enter');

    $assert_session->waitForElementVisible('css', '.coh-style-facet-accordion');
    $facets = $assert_session->elementExists('css', '.coh-style-facet-accordion');

    // Get the container which holds the facets, and assert that, initially,
    // the content type facet is visible but none of the dependent facets are.
    $this->assertFacetLinkExists($facets, TRUE);
    foreach ($node_types as $node_type_id => $type) {
      // Clear all selected facets.
      $this->drupalGet('/search');
      $node_type_label = $type->label();
      $assert_session->elementExists('css', '.views-element-container .coh-style-search-block')->fillField('keywords', 'Test');
      $assert_session->elementExists('css', '.views-element-container input.button')->keyPress('enter');
      // Assert that the search by title shows the proper result.
      $this->assertLinkExists('Test published ' . $node_type_label);
      $this->assertLinkNotExists('Test unpublished ' . $node_type_label);

      // Activate the facet for this content type.
      /** @var \Behat\Mink\Element\NodeElement $linkElement */
      $linkElement = $this->assertLinkExists($node_type_label . ' (1)', $facets);
      $linkElement->click();

      $this->assertLinkExists('Test published ' . $node_type_label);
      $this->assertLinkNotExists('Test unpublished ' . $node_type_label);

      // Pages have no facets.
      if ($node_type_id !== 'page') {
        // Open the accordion item for the "type" taxonomy of this content type.
        // @todo This is commented out because, at the moment, the facets are
        // expanded by default. If we change them to be collapsed by default, we
        // can uncomment this line.
        // $this->assertLinkExists("$node_type_label Type", $facets)->click();
        // Check if term facet is working properly.
        $assert_session->elementExists('css', '.coh-style-facet-accordion')->clickLink($node_type_label . ' Music (1)');
        // Assert that the clear filter is present.
        $assert_session->linkExists('Clear filter(s)');
        // Check if node of the selected term is shown.
        $this->assertLinkExists('Test published ' . $node_type_label);
        $this->assertLinkNotExists('Test unpublished ' . $node_type_label);
        $assert_session->linkNotExists($node_type_label . ' Rocks (1)');
      }
    }
  }

  /**
   * Tests autocomplete search functionality.
   */
  public function testAutocomplete(): void {
    $page = $this->getSession()->getPage();
    $node_types = NodeType::loadMultiple();

    foreach ($node_types as $type) {
      $node_type_label = $type->label();
      $this->drupalGet('/search');
      $this->getSearch()->showSearch();
      $page->fillField('keywords', $node_type_label);

      // By default autocomplete dropdown does not appears after
      // filling field value so, let's trigger keydown event to open it.
      $this->getSession()->executeScript("jQuery('#edit-keywords--2').trigger('keydown')");
      /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
      $assert_session = $this->assertSession();
      $autocomplete_results = $assert_session->waitForElementVisible('css', '.search-api-autocomplete-search');
      $this->assertNotEmpty($autocomplete_results);

      $published_title = 'Test published ' . $node_type_label;
      $unpublished_title = 'Test unpublished ' . $node_type_label;

      // Assert that autocomplete dropdown contains the title
      // of published node of the particular node type.
      $assert_session->elementExists('css', 'span:contains("' . $published_title . '")', $autocomplete_results);

      // Assert that autocomplete dropdown does not contains
      // the title of unpublished node of the particular node type.
      $assert_session->elementNotExists('css', 'span:contains("' . $unpublished_title . '")', $autocomplete_results);
    }

  }

  /**
   * Asserts that a link exists.
   *
   * @param string $title
   *   The title, text, or rel of the link.
   * @param \Behat\Mink\Element\ElementInterface|null $container
   *   (optional) The element that contains the link.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The link element.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  private function assertLinkExists(string $title, ElementInterface $container = NULL): ?ElementInterface {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert */
    return $this->assertSession()->elementExists('named', ['link', $title], $container);
  }

  /**
   * Asserts that a text exists.
   *
   * @param string $title
   *   The title, text, or rel of the link.
   * @param \Behat\Mink\Element\ElementInterface|null $container
   *   (optional) The element that contains the link.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The link element.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  private function assertElementWithTitleExists(string $title, ElementInterface $container = NULL): ElementInterface {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert */
    return $this->assertSession()->elementExists('named', ['content', $title], $container);
  }

  /**
   * Asserts that a link does not exist.
   *
   * @param string $title
   *   The title, text, or rel of the link.
   *
   * @return \Behat\Mink\Element\ElementInterface|null
   *   The link element.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  private function assertLinkNotExists(string $title): ?ElementInterface {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert */
    return $this->assertSession()->elementNotExists('named', ['link', $title]);
  }

  /**
   * Tests that the listing page displays a fallback view if needed.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testFallback(): void {
    // Simulate an unavailable search backend, which is the only condition under
    // which we display the fallback view.
    $this->setBackendAvailability(FALSE);

    $account = $this->createUser();
    $account->addRole('content_administrator');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/search');
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert */
    $facets = $this->assertSession()->elementExists('css', '.coh-style-facet-accordion');
    $this->assertFacetLinkExists($facets, FALSE);
  }

  /**
   * Assert that certain facet links are available on search page.
   *
   * @param \Behat\Mink\Element\ElementInterface|null $facets
   *   The facet container.
   * @param bool $title
   *   TRUE/FALSE.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  private function assertFacetLinkExists(ElementInterface $facets = NULL, bool $title = FALSE) {
    // Get the container which holds the facets, and assert that, initially, the
    // Test that none of the dependent facets are visible for fallback.
    /** @var \Behat\Mink\Element\NodeElement $titleElement */
    $titleElement = $this->assertElementWithTitleExists('Content Type', $facets);
    if ($title) {
      $this->assertTrue($titleElement->isVisible());
    }
    else {
      $this->assertFalse($titleElement->isVisible());
    }

    foreach (['Article Type', 'Event Type', 'Person Type', 'Place Type'] as $facet) {
      /** @var \Behat\Mink\Element\NodeElement $linkElement */
      $linkElement = $this->assertLinkExists($facet, $facets);
      $this->assertFalse($linkElement->isVisible());
    }
    $this->assertLinksExistInOrder();
  }

  /**
   * Returns the view entity for the listing page.
   *
   * @return \Drupal\views\Entity\View
   *   The listing page's view.
   */
  protected function getView(): View {
    return View::load('search');
  }

  /**
   * {@inheritdoc}
   */
  protected function getLinks(): array {
    $links = $this->getSession()
      ->getPage()
      ->findAll('css', 'article a');

    $map = function (ElementInterface $link) {
      return $link->getText();
    };
    return array_map($map, $links);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedLinks(): array {
    return [
      'Test published Article',
      'Test published Event',
      'Test published Person',
      'Test published Place',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    $this->setBackendAvailability(TRUE);
    parent::tearDown();
  }

  /**
   * Waits for the search container.
   *
   * @return \Drupal\Tests\acquia_cms\ExistingSiteJavascript\Search
   *   A wrapper object for interacting with Cohesion's search container.
   */
  protected function getSearch(): Search {
    $element = $this->waitForElementVisible('css', '.search-toggle-button', $this->getSession()->getPage());
    return new Search($element->getXpath(), $this->getSession());
  }

}
