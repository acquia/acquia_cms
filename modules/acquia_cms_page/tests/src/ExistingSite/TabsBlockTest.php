<?php

namespace Drupal\Tests\acquia_cms_page\ExistingSite;

use Drupal\Component\Serialization\Yaml;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests that the Drupal core tabs block behaves as expected.
 *
 * @group acquia_cms_common
 * @group acquia_cms
 * @group low_risk
 * @group pr
 * @group push
 */
class TabsBlockTest extends ExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // If the samlauth module is installed, ensure that it is configured (in
    // this case, using its own test data) to avoid errors when creating user
    // accounts in this test.
    if ($this->container->get('module_handler')->moduleExists('samlauth')) {
      $path = $this->container->get('extension.list.module')
        ->getPath('samlauth');
      $data = file_get_contents("$path/test_resources/samlauth.authentication.yml");
      $data = Yaml::decode($data);

      $this->container->get('config.factory')
        ->getEditable('samlauth.authentication')
        ->setData($data)
        ->save();
    }
  }

  /**
   * Tests that the tabs block appears on node pages.
   *
   * @param string $role
   *   The ID of the user role to test with.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @dataProvider providerTabsBlock
   */
  public function testTabsBlock(string $role) {
    $account = $this->createUser();
    $account->addRole($role);
    $account->save();
    $this->drupalLogin($account);

    $assert_session = $this->assertSession();

    $node_types = [
      'page',
    ];

    if ($this->container->get('module_handler')->moduleExists('acquia_cms_site_studio')) {
      foreach ($node_types as $node_type) {
        $node = $this->createNode([
          'type' => $node_type,
        ]);
        $this->assertSame($account->id(), $node->getOwnerId());
        $this->drupalGet($node->toUrl());
        $assert_session->elementExists('css', 'nav.tabs');
      }
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
