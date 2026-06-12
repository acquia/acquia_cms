<?php

namespace Drupal\Tests\acquia_cms_search\Unit;

use Drupal\acquia_cms_search\Plugin\Block\ClearFacetFilters;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Unit tests for the ClearFacetFilters block.
 *
 * @group acquia_cms_search
 * @group low_risk
 * @group pr
 * @group push
 * @coversDefaultClass \Drupal\acquia_cms_search\Plugin\Block\ClearFacetFilters
 */
class ClearFacetFiltersTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Creates a ClearFacetFilters block instance with given services.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The route match service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   *
   * @return \Drupal\acquia_cms_search\Plugin\Block\ClearFacetFilters
   *   The block instance.
   */
  protected function createBlock(CurrentRouteMatch $route_match, RequestStack $request_stack): ClearFacetFilters {
    return new ClearFacetFilters(
      [],
      'clear_facet_filters',
      ['id' => 'clear_facet_filters', 'provider' => 'acquia_cms_search'],
      $route_match,
      $request_stack
    );
  }

  /**
   * Tests that the block renders a clear link in query-string mode.
   *
   * When the facet source uses the query-string URL processor and a facet is
   * applied via ?f[...], the block must render a "Clear filter(s)" link.
   *
   * @covers ::build
   */
  public function testBuildWithQueryStringFacetRendersLink(): void {
    $route_match = $this->createMock(CurrentRouteMatch::class);
    $route_match->method('getParameter')
      ->with('facets_query')
      ->willReturn(NULL);
    $route_match->method('getRouteName')->willReturn('acquia_search');

    $request = Request::create('/search', 'GET', [
      'f' => ['type:article'],
      'keywords' => 'test',
    ]);
    $request_stack = $this->createMock(RequestStack::class);
    $request_stack->method('getCurrentRequest')->willReturn($request);

    $build = $this->createBlock($route_match, $request_stack)->build();

    $this->assertNotEmpty($build);
    $this->assertEquals('link', $build['#type']);
  }

  /**
   * Tests that the clear link removes the 'f' query parameter from the URL.
   *
   * @covers ::build
   */
  public function testBuildWithQueryStringFacetRemovesFParam(): void {
    $route_match = $this->createMock(CurrentRouteMatch::class);
    $route_match->method('getParameter')
      ->with('facets_query')
      ->willReturn(NULL);
    $route_match->method('getRouteName')->willReturn('acquia_search');

    $request = Request::create('/search', 'GET', [
      'f' => ['type:article'],
      'keywords' => 'test',
    ]);
    $request_stack = $this->createMock(RequestStack::class);
    $request_stack->method('getCurrentRequest')->willReturn($request);

    $build = $this->createBlock($route_match, $request_stack)->build();

    /** @var \Drupal\Core\Url $url */
    $url = $build['#url'];
    $query = $url->getOption('query');
    $this->assertArrayNotHasKey('f', $query);
    $this->assertArrayHasKey('keywords', $query);
  }

  /**
   * Tests that the block returns empty when no facets are active.
   *
   * @covers ::build
   */
  public function testBuildWithoutActiveFacetReturnsEmpty(): void {
    $route_match = $this->createMock(CurrentRouteMatch::class);
    $route_match->method('getParameter')
      ->with('facets_query')
      ->willReturn(NULL);

    $request = Request::create('/search');
    $request_stack = $this->createMock(RequestStack::class);
    $request_stack->method('getCurrentRequest')->willReturn($request);

    $build = $this->createBlock($route_match, $request_stack)->build();

    $this->assertEmpty($build);
  }

}
