<?php

namespace Drupal\Tests\acquia_cms_search\ExistingSite;

use Drupal\acquia_cms_search\Facade\FacetFacade;
use Drupal\facets\Entity\Facet;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests Facet Creation and Search Page Block Placement.
 *
 * @group acquia_cms
 * @group acquia_cms_search
 * @group low_risk
 * @group pr
 * @group push
 */
class AcquiaFacetCreationTest extends ExistingSiteBase {

  use TaxonomyTestTrait;
  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Setup the Facets and Create Content to test search page functionality.
    /** @var \Drupal\taxonomy\VocabularyInterface $vocabulary */
    $vocabulary = Vocabulary::load('categories');
    $sub_type = $this->createTerm($vocabulary, ['name' => 'Test Category']);
    $modules = $this->container->get('module_handler');

    // Create facet & content only if the content type module exists.
    if ($modules->moduleExists('acquia_cms_person')) {
      $this->createNode([
        'type' => 'person',
        'field_categories' => $sub_type->id(),
        'moderation_state' => 'published',
      ]);
      // Check to see if the facet has not been created already.
      $facet = Facet::load('test_category_facet');
      if (empty($facet)) {
        // Use the Facade Class to create the facet.
        \Drupal::classResolver(FacetFacade::class)->addFacet('test_category_facet', 'Test Category Facet',
        'category', 'field_categories', 'taxonomy_term_coder', 'search_api:views_page__search__search');
      }
      // Place the facet block in content region to test facets.
      $block = $this->placeBlock('facet_block:test_category_facet', [
        'region' => 'content',
        'id' => 'test_category_facet',
      ]);
      $this->markEntityForCleanup($block);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  protected static $modules = [
    'acquia_cms_search',
    'acquia_search',
    'search_api_db',
  ];

  /**
   * Tests the recent articles block.
   */
  public function testFacetedSearchPageBlock() {
    // Visit the search page and check that it doesn't throw any error.
    $this->drupalGet('/search');
    /** @var \Drupal\Facets\FacetInterface $facet */
    // Facets will be tested even if content type module is not present.
    $facet = Facet::load('test_category_facet');
    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);
    // Match the CSS only if content type module is present to test the facets.
    if ($this->container->get('module_handler')->moduleExists('acquia_cms_person')) {
      if (!empty($facet) && $facet instanceof Facet) {
        $this->assertSame('test_category_facet', $facet->id());
      }
      $assert_session->elementExists('css', 'div.facets-widget-links ul>li.facet-item a>span.facet-item__value');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    // Delete the facet created for testing.
    $facet = Facet::load('test_category_facet');
    if (!empty($facet)) {
      $facet->delete();
    }
    // Delete the term created for testing.
    $term = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => 'Test Category']);
    $term = reset($term);
    $term->delete();
    parent::tearDown();
  }

}
