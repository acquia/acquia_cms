<?php

namespace Drupal\Tests\acquia_cms_common\ExistingSite;

use Behat\Mink\Element\ElementInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests breadcrumbs generated from content type sub-types.
 *
 * @group acquia_cms_common
 * @group acquia_cms
 */
class BreadcrumbTest extends ExistingSiteBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $block = $this->placeBlock('system_breadcrumb_block');
    $this->markEntityForCleanup($block);
  }

  /**
   * Data provider for ::testBreadcrumb().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerBreadcrumb() : array {
    return [
      [
        'article',
        'Blog',
        [
          ['Articles', '/articles'],
          ['Blog', '/articles/type/blog'],
        ],
      ],
      [
        'event',
        'Party',
        [
          ['Events', '/events'],
          ['Party', '/events/type/party'],
        ],
      ],
      [
        'place',
        'Restaurant',
        [
          ['Places', '/places'],
          ['Restaurant', '/places/type/restaurant'],
        ],
      ],
      [
        'person',
        'Techno DJ',
        [
          ['People', '/people'],
          ['Techno DJ', '/people/type/techno-dj'],
        ],
      ],
    ];
  }

  /**
   * Tests the breadcrumbs generated for sub-types of a content type.
   *
   * @param string $node_type
   *   The content type under test.
   * @param string $sub_type
   *   The label of the sub-type taxonomy term to generate.
   * @param array[] $expected_breadcrumb
   *   The expected breadcrumb links, in their expected order. Each element
   *   should be a tuple containing the text of the link, and its target path.
   *
   * @dataProvider providerBreadcrumb
   */
  public function testBreadcrumb(string $node_type, string $sub_type, array $expected_breadcrumb) : void {
    $assert_session = $this->assertSession();

    $vocabulary = Vocabulary::load($node_type . '_type');
    $sub_type = $this->createTerm($vocabulary, ['name' => $sub_type]);

    $node = $this->createNode([
      'type' => $node_type,
      'field_' . $vocabulary->id() => $sub_type->id(),
      'moderation_state' => 'published',
    ]);
    $this->drupalGet($node->toUrl());

    // Create an array of tuples containing the text and target path of every
    // breadcrumb link.
    $map = function (ElementInterface $link) {
      return [
        $link->getText(),
        $link->getAttribute('href'),
      ];
    };
    $breadcrumb = array_map($map, $assert_session->elementExists('css', '#system-breadcrumb + ol')->findAll('css', 'a'));

    $assert_session->statusCodeEquals(200);
    $this->assertSame($expected_breadcrumb, $breadcrumb);
  }

}
