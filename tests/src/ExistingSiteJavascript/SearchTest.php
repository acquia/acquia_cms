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
    $nodeTypes = NodeType::loadMultiple();
    // Create some published and unpublished nodes to assert that the search
    // respects the published status of content.
    foreach ($nodeTypes as $type) {
      $nodeTypeId = $type->id();
      $nodeTypeLabel = $type->label();

      /** @var \Drupal\taxonomy\VocabularyInterface $termVocab */
      $termVocab = Vocabulary::load($nodeTypeId . '_type');
      if ($termVocab) {
        // Creating couple of terms from each vocab type for published and
        // unpublished nodes.
        $music = $this->createTerm($termVocab, ['name' => $nodeTypeLabel . ' Music']);
        $rock = $this->createTerm($termVocab, ['name' => $nodeTypeLabel . ' Rocks']);

        $publishedNodeValues['field_' . $nodeTypeId . '_type'] = $music->id();
        $unpublishedNodeValues['field_' . $nodeTypeId . '_type'] = $rock->id();
      }

      $publishedNode = $this->createNode($publishedNodeValues + [
        'type' => $nodeTypeId,
        'title' => 'Test published ' . $nodeTypeLabel,
        'moderation_state' => 'published',
      ]);
      $this->assertTrue($publishedNode->isPublished());
      $unpublishedNode = $this->createNode($unpublishedNodeValues + [
        'type' => $nodeTypeId,
        'title' => 'Test unpublished ' . $nodeTypeLabel,
        'moderation_state' => 'draft',
      ]);
      $this->assertFalse($unpublishedNode->isPublished());
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
    $nodeTypes = NodeType::loadMultiple();

    $this->drupalGet('/search');
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assertSession */
    $assertSession = $this->assertSession();
    $assertSession->elementExists('css', '.views-element-container .coh-style-search-block')->fillField('keywords', 'Test');
    $assertSession->elementExists('css', '.views-element-container input.button')->keyPress('enter');

    $assertSession->waitForElementVisible('css', '.coh-style-facet-accordion');
    $facets = $assertSession->elementExists('css', '.coh-style-facet-accordion');

    // Get the container which holds the facets, and assert that, initially,
    // the content type facet is visible but none of the dependent facets are.
    $this->assertFacetLinkExists($facets, TRUE);
    foreach ($nodeTypes as $nodeTypeId => $type) {
      // Clear all selected facets.
      $this->drupalGet('/search');
      $nodeTypeLabel = $type->label();
      $assertSession->elementExists('css', '.views-element-container .coh-style-search-block')->fillField('keywords', 'Test');
      $assertSession->elementExists('css', '.views-element-container input.button')->keyPress('enter');
      // Assert that the search by title shows the proper result.
      $this->assertLinkExists('Test published ' . $nodeTypeLabel);
      $unpublishedTitle = 'Test unpublished ' . $nodeTypeLabel;
      $assertSession->elementNotExists('named', ['link', $unpublishedTitle]);

      // Activate the facet for this content type.
      /** @var \Behat\Mink\Element\NodeElement $linkElement */
      $linkElement = $this->assertLinkExists($nodeTypeLabel . ' (1)', $facets);
      $linkElement->click();

      $this->assertLinkExists('Test published ' . $nodeTypeLabel);
      $assertSession->elementNotExists('named', ['link', $unpublishedTitle]);

      // Pages have no facets.
      if ($nodeTypeId !== 'page') {
        // Open the accordion item for the "type" taxonomy of this content type.
        // @todo This is commented out because, at the moment, the facets are
        // expanded by default. If we change them to be collapsed by default, we
        // can uncomment this line.
        // $this->assertLinkExists("$nodeTypeLabel Type", $facets)->click();
        // Check if term facet is working properly.
        $assertSession->elementExists('css', '.coh-style-facet-accordion')->clickLink($nodeTypeLabel . ' Music (1)');
        // Assert that the clear filter is present.
        $assertSession->linkExists('Clear filter(s)');
        // Check if node of the selected term is shown.
        $this->assertLinkExists('Test published ' . $nodeTypeLabel);
        $assertSession->elementNotExists('named', ['link', $unpublishedTitle]);
        $assertSession->linkNotExists($nodeTypeLabel . ' Rocks (1)');
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
  private function assertFacetLinkExists(ElementInterface $facets = NULL, bool $title = FALSE): void {
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

    foreach (['article-type', 'event-type', 'person-type', 'place-type'] as $facet) {
      $this->assertFalse($this->assertSession()->elementExists('css', '#block-search-' . $facet, $facets)->isVisible());
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
