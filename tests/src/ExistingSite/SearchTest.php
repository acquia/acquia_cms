<?php

namespace Drupal\Tests\acquia_cms\ExistingSite;

use Drupal\Tests\acquia_cms\Traits\CohesionTestTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests search functionality that ships with Acquia CMS.
 *
 * @group acquia_cms
 * @group profile
 */
class SearchTest extends ExistingSiteBase {

  use CohesionTestTrait;

  /**
   * Data provider for ::testSearchBlock().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerSearchBlock() {
    return [
      'anonymous user' => [
        NULL,
      ],
      'authenticated user' => [
        [],
      ],
      'content author' => [
        ['content_author'],
      ],
      'content editor' => [
        ['content_editor'],
      ],
      'content administrator' => [
        ['content_administrator'],
      ],
      'administrator' => [
        ['administrator'],
      ],
    ];
  }

  /**
   * Tests the search block that appears in the standard page header.
   *
   * @param string[]|null $roles
   *   The user role(s) to test with, or NULL to test as an anonymous user. If
   *   this is an empty array, the test will run as an authenticated user with
   *   no additional roles.
   *
   * @dataProvider providerSearchBlock
   */
  public function testSearchBlock(?array $roles) {
    if (isset($roles)) {
      $account = $this->createUser();
      array_walk($roles, [$account, 'addRole']);
      $account->save();
      $this->drupalLogin($account);
    }

    $published_node = $this->createNode([
      'type' => 'page',
      'title' => 'Test published',
      'moderation_state' => 'published',
    ]);
    $this->assertTrue($published_node->isPublished());

    $unpublished_node = $this->createNode([
      'type' => 'page',
      'title' => 'Test unpublished',
      'moderation_state' => 'draft',
    ]);
    $this->assertFalse($unpublished_node->isPublished());

    $assert_session = $this->assertSession();
    $this->drupalGet('/node');
    $search_block = $assert_session->elementExists('css', '#views-exposed-form-search-search');
    $search_block->fillField('keywords', 'Test');
    $search_block->pressButton('Search');
    // Assert that the search by title shows the proper result.
    // @todo re-enable Pages in Content Index once ACMS-445 completed.
    // $this->assertLinkExistsByTitle('Test published');
    // $this->assertLinkNotExistsByTitle('Test unpublished');
  }

}
