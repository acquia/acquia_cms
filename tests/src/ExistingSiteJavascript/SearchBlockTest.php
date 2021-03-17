<?php

namespace Drupal\Tests\acquia_cms\ExistingSiteJavascript;

/**
 * Tests search functionality that ships with Acquia CMS.
 *
 * @group acquia_cms
 * @group profile
 * @group medium_risk
 * @group push
 */
class SearchBlockTest extends CohesionComponentTestBase {

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
    $this->getSearch()->showSearch();
    $search_block = $assert_session->elementExists('css', '#views-exposed-form-search-search');
    $search_block->fillField('keywords', 'Test');
    $assert_session->waitForElementVisible('css', '#edit-submit-search')->keyPress('enter');

    // Assert that the search by title shows the proper result.
    $assert_session->linkExists('Test published');
    $assert_session->linkNotExists('Test unpublished');
  }

}
