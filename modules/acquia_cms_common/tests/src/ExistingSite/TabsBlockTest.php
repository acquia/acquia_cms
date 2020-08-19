<?php

namespace Drupal\Tests\acquia_cms_common\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Test Drupal Core tabs block appears for user roles.
 *
 * @group acquia_cms_common
 * @group acquia_cms
 */
class TabsBlockTest extends ExistingSiteBase {

  /**
   * Test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node1;

  /**
   * Test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node2;

  /**
   * Test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node3;

  /**
   * Test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node4;

  /**
   * Test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node5;

  /**
   * Test that on node view page tabs block is appearing.
   *
   * @param string $role
   *   The ID of the user role to test with.
   *
   * @dataProvider providerViewAccess
   */
  public function testTabsBlockAppears(string $role) {
    $account = $this->createUser();
    $account->addRole($role);
    $account->save();
    $this->drupalLogin($account);

    $this->node1 = $this->createNode([
      'type' => 'article',
      'title' => 'Article Page',
      'uid' => $account->id(),
    ]);
    $this->node2 = $this->createNode([
      'type' => 'event',
      'title' => 'Event Page',
      'uid' => $account->id(),
    ]);
    $this->node3 = $this->createNode([
      'type' => 'page',
      'title' => 'Basic Page',
      'uid' => $account->id(),
    ]);
    $this->node4 = $this->createNode([
      'type' => 'person',
      'title' => 'Person Page',
      'uid' => $account->id(),
    ]);
    $this->node5 = $this->createNode([
      'type' => 'place',
      'title' => 'Place Page',
      'uid' => $account->id(),
    ]);

    $this->drupalGet("/node/{$this->node1->id()}");
    $this->assertSession()->elementExists('css', '#block-tabs');
    $this->drupalGet("/node/{$this->node2->id()}");
    $this->assertSession()->elementExists('css', '#block-tabs');
    $this->drupalGet("/node/{$this->node3->id()}");
    $this->assertSession()->elementExists('css', '#block-tabs');
    $this->drupalGet("/node/{$this->node4->id()}");
    $this->assertSession()->elementExists('css', '#block-tabs');
    $this->drupalGet("/node/{$this->node5->id()}");
    $this->assertSession()->elementExists('css', '#block-tabs');
  }

  /**
   * Data provider for testing tabs block on node view page.
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerViewAccess() {
    return [
      ['administrator'],
      ['content_administrator'],
      ['content_editor'],
      ['content_author'],
    ];
  }

}
