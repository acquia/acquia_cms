<?php

namespace Drupal\Tests\acquia_cms_common\ExistingSite;

use Behat\Mink\Element\ElementInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\views\Entity\View;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Provides a base class for testing the listing page for a content type.
 *
 * Each listing page is a faceted, Search API-based view of all content of a
 * particular type. If the Search API backend is down, a more primitive,
 * unfaceted view of content is displayed instead.
 */
abstract class ContentTypeListTestBase extends ExistingSiteBase {

  /**
   * The machine name of the content type under test.
   *
   * It is expected that subclasses will fill this value in.
   *
   * @var string
   */
  protected $nodeType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->assertNotEmpty($this->nodeType);
    $this->assertInstanceOf(NodeType::class, NodeType::load($this->nodeType));

    $vocabulary = Vocabulary::load('categories');
    $this->assertInstanceOf(Vocabulary::class, $vocabulary);
    $categories = [
      $this->createTerm($vocabulary, ['name' => 'Music'])->id(),
      $this->createTerm($vocabulary, ['name' => 'Art'])->id(),
      $this->createTerm($vocabulary, ['name' => 'Literature'])->id(),
      $this->createTerm($vocabulary, ['name' => 'Math'])->id(),
    ];

    $vocabulary_id = $this->nodeType . '_type';
    $vocabulary = Vocabulary::load($vocabulary_id);
    $this->assertInstanceOf(Vocabulary::class, $vocabulary, "$vocabulary_id vocabulary does not exist.");
    $types = [
      $this->createTerm($vocabulary, ['name' => 'Type A'])->id(),
      $this->createTerm($vocabulary, ['name' => 'Type B'])->id(),
      $this->createTerm($vocabulary, ['name' => 'Type O'])->id(),
    ];

    $time = time();

    $this->createNode([
      'type' => $this->nodeType,
      'title' => 'Secret',
      'created' => $time++,
    ]);
    $this->createNode([
      'type' => $this->nodeType,
      'title' => 'Alpha',
      'moderation_state' => 'published',
      'field_categories' => $categories[0],
      'field_' . $this->nodeType . '_type' => $types[0],
      'created' => $time++,
    ]);
    $this->createNode([
      'type' => $this->nodeType,
      'title' => 'Beta',
      'moderation_state' => 'published',
      'field_categories' => $categories[1],
      'field_' . $this->nodeType . '_type' => $types[1],
      'created' => $time++,
    ]);
    $this->createNode([
      'type' => $this->nodeType,
      'title' => 'Charlie',
      'moderation_state' => 'published',
      'field_categories' => $categories[2],
      'field_' . $this->nodeType . '_type' => $types[2],
      'created' => $time++,
    ]);
    $this->createNode([
      'type' => $this->nodeType,
      'title' => 'Delta',
      'moderation_state' => 'published',
      'field_categories' => $categories[3],
      'field_' . $this->nodeType . '_type' => $types[0],
      'created' => $time++,
    ]);
    $this->createNode([
      'type' => $this->nodeType,
      'title' => 'Echo',
      'moderation_state' => 'published',
      'field_categories' => $categories[0],
      'field_' . $this->nodeType . '_type' => $types[1],
      'created' => $time++,
    ]);
    $this->createNode([
      'type' => $this->nodeType,
      'title' => 'Foxtrot',
      'moderation_state' => 'published',
      'field_categories' => $categories[1],
      'field_' . $this->nodeType . '_type' => $types[2],
      'created' => $time++,
    ]);
  }

  /**
   * Toggles the availability of the search backend.
   *
   * This is used to test the fallback view displayed by the listing page if the
   * search backend is down.
   *
   * @param bool $is_available
   *   If TRUE, the view_fallback handler will behave normally. If FALSE, the
   *   handler will behave as if the search backend is down, in order to
   *   facilitate testing that the fallback view appears and looks the way we
   *   expect it to.
   */
  private function setBackendAvailability(bool $is_available) : void {
    $view = $this->getView();
    $display = &$view->getDisplay('default');
    $key = ['display_options', 'empty', 'view_fallback', 'simulate_unavailable'];
    if ($is_available) {
      NestedArray::unsetValue($display, $key);
    }
    else {
      NestedArray::setValue($display, $key, TRUE);
    }
    $view->save();
  }

  /**
   * Returns the view entity for the listing page.
   *
   * @return \Drupal\views\Entity\View
   *   The listing page's view.
   */
  abstract protected function getView() : View;

  /**
   * Visits the listing page.
   */
  abstract protected function visitListPage() : void;

  /**
   * Tests the content type's listing page and the facets on it.
   */
  public function testListPage() {
    // Create user with permission 'View unpublished content'.
    $this->userWithUnpublishedPermission();
    $this->visitListPage();

    $assert_session = $this->assertSession();
    // Assert that all categories facets are available.
    $assert_session->linkExists('Music (2)');
    $assert_session->linkExists('Art (2)');
    $assert_session->linkExists('Literature (1)');
    $assert_session->linkExists('Math (1)');

    // Assert all type facets are available.
    $assert_session->linkExists('Type A (2)');
    $assert_session->linkExists('Type B (2)');
    $assert_session->linkExists('Type O (2)');

    // All content should be visible except for the secret one.
    $this->assertLinksExistInOrder();
    $assert_session->linkNotExists('Secret');

    // Filter by a category and ensure that the expected content is visible.
    $page = $this->getSession()->getPage();
    $page->clickLink('Art (2)');
    $assert_session->addressMatches('/.\/category\/art/');
    $this->assertLinksExistInOrder(['Foxtrot', 'Beta']);
    $assert_session->linkNotExists('Alpha');
    $assert_session->linkNotExists('Charlie');
    $assert_session->linkNotExists('Delta');
    $assert_session->linkNotExists('Echo');
    $assert_session->linkNotExists('Secret');

    // The choice of a category should narrow down the results in the type
    // facet.
    $assert_session->linkNotExists('Type A');
    $assert_session->linkExists('Type B (1)');
    $assert_session->linkExists('Type O (1)');

    // Filtering by type should narrow the results down even more.
    $page->clickLink('Type O (1)');
    $assert_session->addressMatches('/.\/type\/type-o\/category\/art/');
    $assert_session->linkNotExists('Alpha');
    $assert_session->linkNotExists('Beta');
    $assert_session->linkNotExists('Charlie');
    $assert_session->linkNotExists('Delta');
    $assert_session->linkNotExists('Echo');
    $assert_session->linkExists('Foxtrot');
    $assert_session->linkNotExists('Secret');

    // Removing a facet should widen the results.
    $page->clickLink('Art (1)');
    $assert_session->addressMatches('/.\/type\/type-o/');
    $this->assertLinksExistInOrder(['Foxtrot', 'Charlie']);
    $assert_session->linkNotExists('Alpha');
    $assert_session->linkNotExists('Beta');
    $assert_session->linkNotExists('Delta');
    $assert_session->linkNotExists('Echo');
    $assert_session->linkNotExists('Secret');
  }

  /**
   * Tests that the listing page displays a fallback view if needed.
   */
  public function testFallback() {
    // Simulate an unavailable search backend, which is the only condition under
    // which we display the fallback view.
    $this->setBackendAvailability(FALSE);

    // Create user with permission 'View unpublished content'.
    $this->userWithUnpublishedPermission();

    $this->visitListPage();
    $assert_session = $this->assertSession();

    // Assert that all categories facets are unavailable.
    $assert_session->linkNotExists('Music (2)');
    $assert_session->linkNotExists('Art (2)');
    $assert_session->linkNotExists('Literature (1)');
    $assert_session->linkNotExists('Math (1)');

    // Assert all type facets are unavailable.
    $assert_session->linkNotExists('Type A (2)');
    $assert_session->linkNotExists('Type B (2)');
    $assert_session->linkNotExists('Type O (2)');

    // All content should be visible except for the secret one.
    $this->assertLinksExistInOrder();
    $assert_session->linkNotExists('Secret');
  }

  /**
   * Asserts that a set of links are on the page, in a specific order.
   *
   * @param string[] $expected_links_in_order
   *   (optoinal) The titles of the links we expect to find, in the order that
   *   we expect them to appear on the page. If not provided, this method will
   *   search for links to all published content of the type under test.
   */
  private function assertLinksExistInOrder(array $expected_links_in_order = NULL) : void {
    if ($expected_links_in_order) {
      $count = count($expected_links_in_order);
      $expected_links_in_order = array_intersect($this->getLinksInOrder(), $expected_links_in_order);
      $this->assertCount($count, $expected_links_in_order);
    }
    else {
      $expected_links_in_order = $this->getLinksInOrder();
    }
    $expected_links_in_order = array_values($expected_links_in_order);

    $actual_links = $this->getSession()
      ->getPage()
      ->findAll('css', 'a[title]');

    $map = function (ElementInterface $link) {
      // Our template for node teasers doesn't actually link the title -- which
      // is probably an accessibility no-no, but let's not get into that now --
      // but it does include a 'title' attribute in the "read more" link which
      // contains the actual title of the linked node.
      return $link->getAttribute('title');
    };
    $actual_links = array_map($map, $actual_links);
    $actual_links = array_intersect($actual_links, $expected_links_in_order);
    $actual_links = array_values($actual_links);

    $this->assertSame($actual_links, $expected_links_in_order);
  }

  /**
   * Returns the titles of all content of the type under test.
   *
   * @return string[]
   *   The titles of all published content of the type under test, in the order
   *   we would expect to see them on the listing page.
   */
  protected function getLinksInOrder() : array {
    $ids = $this->getQuery()->execute();

    /** @var \Drupal\node\NodeInterface[] $content */
    $content = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->loadMultiple($ids);

    $map = function (NodeInterface $node) {
      return $node->getTitle();
    };
    return array_map($map, $content);
  }

  /**
   * Builds a query for all published content of the type under test.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query object to find all published content of the type under
   *   test.
   */
  protected function getQuery() : QueryInterface {
    return $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->getQuery()
      ->condition('type', $this->nodeType)
      ->condition('status', TRUE);
  }

  /**
   * Create user with permission 'Any unpublished content'.
   */
  private function userWithUnpublishedPermission() : void {
    $account = $this->createUser(['bypass node access']);
    $account->save();
    $this->drupalLogin($account);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->setBackendAvailability(TRUE);
    parent::tearDown();
  }

}
