<?php

namespace Drupal\Tests\acquia_cms\ExistingSite;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests redirection upon user login.
 *
 * @group acquia_cms
 */
class LoginRedirectionTest extends ExistingSiteBase {

  use StringTranslationTrait;

  /**
   * Whether login redirect handling was enabled before the test case began.
   *
   * @var bool
   */
  private $enabled;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->enabled = $this->container->get('config.factory')
      ->get('acquia_cms.settings')
      ->get('user_login_redirection');
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->container->get('config.factory')
      ->getEditable('acquia_cms.settings')
      ->set('user_login_redirection', $this->enabled)
      ->save();
    parent::tearDown();
  }

  /**
   * Tests special redirect handling upon user login.
   *
   * Tests special redirect handling upon user login with destination.
   *
   * @param bool $enable
   *   Whether or not to enable special redirect handling.
   * @param string[] $destination
   *   The expected destination upon logging in.
   * @param string[] $roles
   *   Additional user roles to apply to the account being logged in.
   *
   * @dataProvider providerLoginDestination
   */
  public function testLoginDestination(bool $enable, array $destination = [], array $roles = []): void {
    $this->container->get('config.factory')
      ->getEditable('acquia_cms.settings')
      ->set('user_login_redirection', $enable)
      ->save();

    $account = $this->createUser();
    array_walk($roles, [$account, 'addRole']);
    $account->save();

    foreach ($destination as $key) {
      list($destination_parameter, $expected_destination_after_login) = str_replace('{uid}', $account->id(), $key);
      $edit = [
        'name' => $account->getAccountName(),
        'pass' => $account->passRaw,
      ];
      $this->drupalPostForm(Url::fromRoute('user.login'), $edit, $this->t('Log in'), ['query' => ['destination' => $destination_parameter]]);
      $this->assertSession()->addressEquals($expected_destination_after_login);
      $this->drupalLogout();
    }
  }

  /**
   * Data provider for ::testLoginDestination().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerLoginDestination(): array {
    return [
      'content author with redirect and destination' => [
        TRUE,
        [
          ['', '/user/{uid}/moderation/dashboard'],
          ['user', '/user/{uid}/moderation/dashboard'],
          ['/user', '/user/{uid}/moderation/dashboard'],
          ['/user/', '/user/{uid}/moderation/dashboard'],
          ['/user/{uid}', '/user/{uid}/moderation/dashboard'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['content_author'],
      ],
      'content editor with redirect and destination' => [
        TRUE,
        [
          ['', '/user/{uid}/moderation/dashboard'],
          ['user', '/user/{uid}/moderation/dashboard'],
          ['/user', '/user/{uid}/moderation/dashboard'],
          ['/user/', '/user/{uid}/moderation/dashboard'],
          ['/user/{uid}', '/user/{uid}/moderation/dashboard'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['content_editor'],
      ],
      'content administrator with redirect and destination' => [
        TRUE,
        [
          ['', '/user/{uid}/moderation/dashboard'],
          ['user', '/user/{uid}/moderation/dashboard'],
          ['/user', '/user/{uid}/moderation/dashboard'],
          ['/user/', '/user/{uid}/moderation/dashboard'],
          ['/user/{uid}', '/user/{uid}/moderation/dashboard'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['content_administrator'],
      ],
      'administrator with redirect and destination' => [
        TRUE,
        [
          ['', '/user/{uid}/moderation/dashboard'],
          ['user', '/user/{uid}/moderation/dashboard'],
          ['/user', '/user/{uid}/moderation/dashboard'],
          ['/user/', '/user/{uid}/moderation/dashboard'],
          ['/user/{uid}', '/user/{uid}/moderation/dashboard'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['administrator'],
      ],
      'site builder with redirect and destination' => [
        TRUE,
        [
          ['', '/admin/cohesion'],
          ['user', '/admin/cohesion'],
          ['/user', '/admin/cohesion'],
          ['/user/', '/admin/cohesion'],
          ['/user/{uid}', '/admin/cohesion'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['site_builder'],
      ],
      'developer with redirect and destination' => [
        TRUE,
        [
          ['', '/admin/cohesion'],
          ['user', '/admin/cohesion'],
          ['/user', '/admin/cohesion'],
          ['/user/', '/admin/cohesion'],
          ['/user/{uid}', '/admin/cohesion'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['developer'],
      ],
      'user administrator with redirect and destination' => [
        TRUE,
        [
          ['', '/admin/people'],
          ['user', '/admin/people'],
          ['/user', '/admin/people'],
          ['/user/', '/admin/people'],
          ['/user/{uid}', '/admin/people'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['user_administrator'],
      ],
      'content author+site builder with redirect and destination' => [
        TRUE,
        [
          ['', '/user/{uid}/moderation/dashboard'],
          ['user', '/user/{uid}/moderation/dashboard'],
          ['/user', '/user/{uid}/moderation/dashboard'],
          ['/user/', '/user/{uid}/moderation/dashboard'],
          ['/user/{uid}', '/user/{uid}/moderation/dashboard'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['content_author', 'site_builder'],
      ],
      'content author+user administrator with redirect and destination' => [
        TRUE,
        [
          ['', '/user/{uid}/moderation/dashboard'],
          ['user', '/user/{uid}/moderation/dashboard'],
          ['/user', '/user/{uid}/moderation/dashboard'],
          ['/user/', '/user/{uid}/moderation/dashboard'],
          ['/user/{uid}', '/user/{uid}/moderation/dashboard'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['content_author', 'user_administrator'],
      ],
      'site builder+user administrator with redirect and destination' => [
        TRUE,
        [
          ['', '/admin/cohesion'],
          ['user', '/admin/cohesion'],
          ['/user', '/admin/cohesion'],
          ['/user/', '/admin/cohesion'],
          ['/user/{uid}', '/admin/cohesion'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['site_builder', 'user_administrator'],
      ],
      'content author+site builder+user administrator with redirect and destination' => [
        TRUE,
        [
          ['', '/user/{uid}/moderation/dashboard'],
          ['user', '/user/{uid}/moderation/dashboard'],
          ['/user', '/user/{uid}/moderation/dashboard'],
          ['/user/', '/user/{uid}/moderation/dashboard'],
          ['/user/{uid}', '/user/{uid}/moderation/dashboard'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['content_author', 'site_builder', 'user_administrator'],
      ],
      'site builder without redirect and destination' => [
        FALSE,
        [
          ['', '/user/{uid}'],
          ['user', '/user/{uid}'],
          ['/user', '/user/{uid}'],
          ['/user/', '/user/{uid}'],
          ['/user/{uid}', '/user/{uid}'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['site_builder'],
      ],
      'developer without redirect and destination' => [
        FALSE,
        [
          ['', '/user/{uid}'],
          ['user', '/user/{uid}'],
          ['/user', '/user/{uid}'],
          ['/user/', '/user/{uid}'],
          ['/user/{uid}', '/user/{uid}'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['developer'],
      ],
      'user administrator without redirect and destination' => [
        FALSE,
        [
          ['', '/user/{uid}'],
          ['user', '/user/{uid}'],
          ['/user', '/user/{uid}'],
          ['/user/', '/user/{uid}'],
          ['/user/{uid}', '/user/{uid}'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['user_administrator'],
      ],
      'content administrator without redirect and destination' => [
        FALSE,
        [
          ['', '/user/{uid}/moderation/dashboard'],
          ['user', '/user/{uid}'],
          ['/user', '/user/{uid}'],
          ['/user/', '/user/{uid}'],
          ['/user/{uid}', '/user/{uid}'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['content_administrator'],
      ],
      'content author without redirect and destination' => [
        FALSE,
        [
          ['', '/user/{uid}/moderation/dashboard'],
          ['user', '/user/{uid}'],
          ['/user', '/user/{uid}'],
          ['/user/', '/user/{uid}'],
          ['/user/{uid}', '/user/{uid}'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['content_author'],
      ],
      'content editor without redirect and destination' => [
        FALSE,
        [
          ['', '/user/{uid}/moderation/dashboard'],
          ['user', '/user/{uid}'],
          ['/user', '/user/{uid}'],
          ['/user/', '/user/{uid}'],
          ['/user/{uid}', '/user/{uid}'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['content_editor'],
      ],
      'administrator without redirect and destination' => [
        FALSE,
        [
          ['', '/user/{uid}/moderation/dashboard'],
          ['user', '/user/{uid}'],
          ['/user', '/user/{uid}'],
          ['/user/', '/user/{uid}'],
          ['/user/{uid}', '/user/{uid}'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['administrator'],
      ],
      'content author+site builder without redirect and destination' => [
        FALSE,
        [
          ['', '/user/{uid}/moderation/dashboard'],
          ['user', '/user/{uid}'],
          ['/user', '/user/{uid}'],
          ['/user/', '/user/{uid}'],
          ['/user/{uid}', '/user/{uid}'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['content_author', 'site_builder'],
      ],
      'content author+user administrator without redirect and destination' => [
        FALSE,
        [
          ['', '/user/{uid}/moderation/dashboard'],
          ['user', '/user/{uid}'],
          ['/user', '/user/{uid}'],
          ['/user/', '/user/{uid}'],
          ['/user/{uid}', '/user/{uid}'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['content_author', 'user_administrator'],
      ],
      'site builder+user administrator without redirect and destination' => [
        FALSE,
        [
          ['', '/user/{uid}'],
          ['user', '/user/{uid}'],
          ['/user', '/user/{uid}'],
          ['/user/', '/user/{uid}'],
          ['/user/{uid}', '/user/{uid}'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['site_builder', 'user_administrator'],
      ],
      'content author+site builder+user administrator without redirect and destination' => [
        FALSE,
        [
          ['', '/user/{uid}/moderation/dashboard'],
          ['user', '/user/{uid}'],
          ['/user', '/user/{uid}'],
          ['/user/', '/user/{uid}'],
          ['/user/{uid}', '/user/{uid}'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/node/add', '/node/add'],
        ],
        ['content_author', 'site_builder', 'user_administrator'],
      ],
    ];
  }

}
