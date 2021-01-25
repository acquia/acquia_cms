<?php

namespace Drupal\Tests\acquia_cms_common\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests that the Drupal core tabs block behaves as expected.
 *
 * @group acquia_cms_common
 * @group acquia_cms
 * @group risky
 */
class TabsBlockTest extends ExistingSiteBase {

  /**
   * Tests that the tabs block appears on node pages.
   *
   * @param string $role
   *   The ID of the user role to test with.
   *
   * @dataProvider providerTabsBlock
   */
  public function testTabsBlock(string $role) {
    $account = $this->createUser();
    $account->addRole($role);
    $account->save();
    $this->drupalLogin($account);

    $assert_session = $this->assertSession();

    $node_types = $this->container->get('entity_type.manager')
      ->getStorage('node_type')
      ->getQuery()
      ->execute();

    foreach ($node_types as $node_type) {
      $node = $this->createNode([
        'type' => $node_type,
      ]);
      $this->assertSame($account->id(), $node->getOwnerId());
      $this->drupalGet($node->toUrl());
      $assert_session->elementExists('css', '#block-tabs');
    }
  }

  /**
   * Data provider for ::testTabsBlock().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerTabsBlock() {
    return [
      ['administrator'],
      ['content_administrator'],
      ['content_editor'],
      ['content_author'],
    ];
  }

}
