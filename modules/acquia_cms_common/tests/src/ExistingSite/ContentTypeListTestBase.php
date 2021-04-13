<?php

namespace Drupal\Tests\acquia_cms_common\ExistingSite;

use Behat\Mink\Element\ElementInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\acquia_cms_common\Traits\AssertLinksTrait;
use Drupal\Tests\acquia_cms_common\Traits\SetBackendAvailabilityTrait;
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

  use AssertLinksTrait;
  use SetBackendAvailabilityTrait;

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
  protected function setUp(): void {
    parent::setUp();

    $langcode = 'es';
    if (!ConfigurableLanguage::load($langcode)) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

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

    $node = $this->createNode([
      'type' => $this->nodeType,
      'title' => 'Alpha',
      'moderation_state' => 'published',
      'field_categories' => $categories[0],
      'field_' . $this->nodeType . '_type' => $types[0],
      'created' => $time++,
    ]);

    // Create a translation for the node.
    $translate_node = $node->toArray();
    $translate_node['title'] = 'Spanish - Alpha';
    $node->addTranslation('es', $translate_node)->save();

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

    // Update additional field value.
    $this->updateNodeFieldValues();
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
   *
   * @param string $langcode
   *   Langcode to visit tranlated page.
   */
  abstract protected function visitListPage($langcode = NULL) : void;

  /**
   * Update specific field value for nodes.
   */
  protected function updateNodeFieldValues() : void {}

  /**
   * Data provider for testing the listing page with different permissions.
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function permissionProvider() : array {
    return [
      'anonymous user' => [NULL],
      // Search API is really stupid about node access, and does not properly
      // support Content Moderation. This is addressed by
      // https://www.drupal.org/project/search_api/issues/3075684, so we should
      // change this to a more restrictive permission, like "view any
      // unpublished content" when that issue is fixed (or we bring in the patch
      // directly).
      'view unpublished' => [['bypass node access']],
    ];
  }

  /**
   * Tests the content type's listing page and the facets on it.
   *
   * @param string[] $permissions
   *   (optional) A set of permissions with which to run this test. If omitted,
   *   the test is run as the anonymous user.
   *
   * @dataProvider permissionProvider
   */
  public function testListPage(array $permissions = NULL) {
    if (isset($permissions)) {
      $account = $this->createUser($permissions);
      $this->drupalLogin($account);
    }
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
    $assert_session->linkNotExists('Spanish - Alpha');

    // Filter by a category and ensure that the expected content is visible.
    $page = $this->getSession()->getPage();
    $page->clickLink('Art (2)');
    // Assert that the clear filter is present.
    $assert_session->linkExists('Clear filter(s)');

    $assert_session->addressMatches('/.\/category\/art-[0-9]/');
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
    $assert_session->addressMatches('/.\/type\/type-o-.*\/category\/art-.*/');
    $assert_session->linkNotExists('Alpha');
    $assert_session->linkNotExists('Beta');
    $assert_session->linkNotExists('Charlie');
    $assert_session->linkNotExists('Delta');
    $assert_session->linkNotExists('Echo');
    $assert_session->linkExists('Foxtrot');
    $assert_session->linkNotExists('Secret');

    // Removing a facet should widen the results.
    $page->clickLink('Art (1)');
    $assert_session->addressMatches('/.\/type\/type-o-.*/');
    $this->assertLinksExistInOrder(['Foxtrot', 'Charlie']);
    $assert_session->linkNotExists('Alpha');
    $assert_session->linkNotExists('Beta');
    $assert_session->linkNotExists('Delta');
    $assert_session->linkNotExists('Echo');
    $assert_session->linkNotExists('Secret');

    // Assert translated items on translated-list page.
    $this->visitListPage('es');
    $assert_session->linkExists('Spanish - Alpha');

    // Assert filtered nodes on search page.
    $options = [
      'query' => ['keywords' => 'Alpha'],
    ];
    $this->drupalGet('/search', $options);
    $assert_session->linkExists('Alpha');
    $assert_session->linkNotExists('Spanish - Alpha');

    $this->drupalGet('/es/search', $options);
    $assert_session->linkExists('Spanish - Alpha');
  }

  /**
   * Tests that the listing page displays a fallback view if needed.
   *
   * @param string[] $permissions
   *   (optional) A set of permissions with which to run this test. If omitted,
   *   the test is run as the anonymous user.
   *
   * @dataProvider permissionProvider
   */
  public function testFallback(array $permissions = NULL) {
    // Simulate an unavailable search backend, which is the only condition under
    // which we display the fallback view.
    $this->setBackendAvailability(FALSE);

    if (isset($permissions)) {
      $account = $this->createUser($permissions);
      $this->drupalLogin($account);
    }

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
   * {@inheritdoc}
   */
  protected function getLinks() : array {
    $links = $this->getSession()
      ->getPage()
      ->findAll('css', 'article a.card-link');

    $map = function (ElementInterface $link) {
      return $link->getText();
    };
    return array_map($map, $links);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedLinks() : array {
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
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->setBackendAvailability(TRUE);
    parent::tearDown();
  }

}
