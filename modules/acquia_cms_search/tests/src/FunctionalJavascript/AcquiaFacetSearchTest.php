<?php

namespace Drupal\Tests\acquia_cms_search\FunctionalJavascript;

use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests facet creation and Search Page Block Placement.
 *
 * @group acquia_cms_search
 * @group low_risk
 */
class AcquiaFacetSearchTest extends BrowserTestBase {
  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'acquia_cms_search',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * {@inheritdoc}
   *
   * @todo Fix config schema for fallback_view & main_listing_pages_view plugin.
   */
  // @codingStandardsIgnoreStart
  protected $strictConfigSchema = FALSE;
  // @codingStandardsIgnoreEnd

  /**
   * The module installer object.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * The module extension list object.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->rootUser);
    $assert_session = $this->assertSession();
    $this->drupalGet('/search');
    $assert_session->statusCodeEquals(200);
    $this->moduleInstaller = $this->container->get('module_installer');
    $this->moduleList = $this->container->get('extension.list.module');
  }

  /**
   * Test category facet when acquia_cms_place module is enabled.
   */
  public function testAcquiaPlaceContentTypeOnSearch() {
    if ($this->installModule('acquia_cms_place')) {
      $this->createSampleContent('place');
      $this->assertElements();
    }
  }

  /**
   * Test category facet when acquia_cms_person module is enabled.
   */
  public function testAcquiaPersonContentTypeOnSearch() {
    if ($this->installModule('acquia_cms_person')) {
      $this->createSampleContent('person');
      $this->assertElements();
    }
  }

  /**
   * Test category facet when acquia_cms_article module is enabled.
   */
  public function testAcquiaArticleContentTypeOnSearch() {
    if ($this->installModule('acquia_cms_article')) {
      $this->createSampleContent('article');
      $this->assertElements();
    }
  }

  /**
   * Test category facet when acquia_cms_event module is enabled.
   */
  public function testAcquiaEventContentTypeOnSearch() {
    if ($this->installModule('acquia_cms_event')) {
      $this->createSampleContent('event');
      $this->assertElements();
    }
  }

  /**
   * Checks if given module exist and tries to enable it.
   *
   * @param string $module
   *   Given module machine_name.
   *
   * @return bool
   *   Returns true|false based on module exist and on successful installation.
   */
  protected function installModule(string $module) {
    try {
      $this->moduleList->get($module);
    }
    catch (UnknownExtensionException $e) {
      return FALSE;
    }
    return $this->moduleInstaller->install([$module]);
  }

  /**
   * Create sample content & terms and place the facet block.
   *
   * @param string $node_type
   *   Machine name for node type.
   */
  protected function createSampleContent(string $node_type) {
    $categories_vocab = Vocabulary::load("categories");
    foreach ([1, 2] as $item) {
      $term = $this->createTerm($categories_vocab, ["name" => "Category $item"]);
      $this->drupalCreateNode([
        'title' => ucfirst($node_type) . " $item",
        'type' => $node_type,
        'body' => ['value' => 'This is the body for node `' . $node_type . '` & category `' . $term->getName() . '`'],
        'field_categories' => ['target_id' => $term->id()],
        'moderation_state' => 'published',
        'status' => 1,
      ]);
    }
    $this->drupalPlaceBlock('facet_block:search_category', ['region' => 'content']);
  }

  /**
   * Assets the facet block & facet links on the search page.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertElements() {
    $this->drupalGet('/search');
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', '.block-facet--links');
    $assert_session->elementsCount('css', '.block-facet--links li.facet-item', 2);
    $assert_session->elementExists('css', '.views-element-container');
    $assert_session->elementsCount('css', '.views-element-container .views-row', 2);
  }

}
