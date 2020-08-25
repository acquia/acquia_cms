<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

use Behat\Mink\Element\ElementInterface;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase;

/**
 * Tests the search functionality that ships with Acquia CMS.
 *
 * @group acquia_cms_search
 * @group acquia_cms
 */
class SearchTest extends ExistingSiteSelenium2DriverTestBase {

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

    // Get the accordion container which holds the facets, and assert that,
    // initially, the content type facet is visible but none of the dependent
    // facets are.
    $accordion_container = $this->assertSession()->elementExists('css', '.coh-accordion-tabs-content-wrapper');
    $this->assertTrue($this->assertLinkExists('Content Type', $accordion_container)->isVisible());
    $this->assertFalse($this->assertLinkExists('Article Type', $accordion_container)->isVisible());
    $this->assertFalse($this->assertLinkExists('Event Type', $accordion_container)->isVisible());
    $this->assertFalse($this->assertLinkExists('Person Type', $accordion_container)->isVisible());
    $this->assertFalse($this->assertLinkExists('Place Type', $accordion_container)->isVisible());

    // Facets should filter the content type and "type" taxonomy as expected,
    // and we should only see published content.
    foreach ($node_types as $node_type_id => $type) {
      // Clear all selected facets.
      $this->drupalGet('/search');

      $node_type_label = $type->label();

      $this->assertLinkExistsByTitle('Test published ' . $node_type_label);
      $this->assertLinkNotExistsByTitle('Test unpublished ' . $node_type_label);

      // Activate the facet for this content type.
      $this->assertLinkExists($node_type_label . ' (1)', $accordion_container)->click();

      $this->assertLinkExistsByTitle('Test published ' . $node_type_label);
      $this->assertLinkNotExistsByTitle('Test unpublished ' . $node_type_label);

      // Pages have no facets.
      if ($node_type_id !== 'page') {
        // Open the accordion item for the "type" taxonomy of this content type.
        $this->assertLinkExists($node_type_label . ' Type', $accordion_container)->click();
        // Check if term facet is working properly.
        $page->clickLink($node_type_label . ' Music (1)');
        // Check if node of the selected term is shown.
        $this->assertLinkExistsByTitle('Test published ' . $node_type_label);
        $this->assertLinkNotExistsByTitle('Test unpublished ' . $node_type_label);
        $assert_session->linkNotExists($node_type_label . ' Rocks (1)');
      }
    }
  }

  /**
   * Asserts that a link exists with the given title attribute.
   *
   * @param string $title
   *   The title of the link.
   */
  private function assertLinkExistsByTitle(string $title) : void {
    $this->assertSession()->elementExists('css', 'a.coh-link[title="' . $title . '"]');
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
   * Asserts that a link with the given title attribute doesn't exist.
   *
   * @param string $title
   *   The title of the link.
   */
  private function assertLinkNotExistsByTitle(string $title) : void {
    $this->assertSession()->elementNotExists('css', 'a.coh-link[title="' . $title . '"]');
  }

}
