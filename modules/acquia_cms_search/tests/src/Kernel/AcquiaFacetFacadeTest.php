<?php

namespace Drupal\Tests\acquia_cms_search\Kernel;

use Drupal\acquia_cms_search\Facade\FacetFacade;
use Drupal\facets\FacetInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests facet creation and Search Page Block Placement.
 *
 * @group acquia_cms_search
 * @group low_risk
 * @group pr
 * @group push
 */
class AcquiaFacetFacadeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'facets',
    'search_api',
    'acquia_cms_search',
  ];

  /**
   * The facets_facet entity object.
   *
   * @var \Drupal\facets\Entity\Facet
   */
  protected $facetEntity;

  /**
   * @var \Drupal\acquia_cms_search\Facade\FacetFacade
   */
  protected $facetFacade;

  /**
   * {@inheritdoc}
   *
   * @todo Fix config schema for fallback_view & main_listing_pages_view plugin.
   */
  // @codingStandardsIgnoreStart
  protected $strictConfigSchema = FALSE;
  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('facets_facet');
    $this->facetFacade = $this->container->get('class_resolver')->getInstanceFromDefinition(FacetFacade::class);
    $this->facetEntity = $this->container->get('entity_type.manager')->getStorage('facets_facet');
  }

  /**
   * Tests facet facade to verify facet entity.
   */
  public function testAcquiaFacetFacade() {
    $this->facetFacade->addFacet([
      'id' => 'search_category_test',
      'name' => 'Category',
      'url_alias' => 'category',
      'field_identifier' => 'field_categories',
    ]);
    $facet = $this->facetEntity->load('search_category_test');
    $this->assertInstanceOf(FacetInterface::class, $facet);
    $this->assertEquals("Category", $facet->getName());
  }

  /**
   * Tests methods of acquia facet facade class.
   */
  public function testAcquiaFacetFacadeMethods() {
    $defaultValues = $this->facetFacade->defaultValues();
    $updateValues = [
      'hard_limit' => 1,
      'id' => 'another_facet_id',
    ];
    $this->assertEquals($updateValues + $defaultValues, $this->facetFacade->mergeValues($updateValues));
  }

}
