<?php

namespace Drupal\Tests\acquia_cms\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests redirection upon user login.
 *
 * @group acquia_cms
 */
class LoginRedirectionTest extends ExistingSiteBase {

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

    // Store the current config flag so we can restore it in tearDown().
    $this->enabled = $this->container->get('config.factory')
      ->get('acquia_cms.settings')
      ->get('user_login_redirection');

    // Create a node with the path '/user-stories', so we can ensure the
    // redirect handler properly handles paths starting with 'user'.
    $this->createNode([
      'type' => 'page',
      'title' => 'User Stories',
      'moderation_state' => 'published',
    ]);
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
   * @param array[] $destination_map
   *   An array of tuples, each containing the value of the 'destination' query
   *   string parameter (or an empty value to omit the parameter), and the path
   *   where the user is expected to land after logging in.
   * @param string[] $roles
   *   Additional user roles to apply to the account being logged in.
   *
   * @dataProvider providerLoginDestination
   */
  public function testLoginDestination(bool $enable, array $destination_map = [], array $roles = []) : void {
    $this->container->get('config.factory')
      ->getEditable('acquia_cms.settings')
      ->set('user_login_redirection', $enable)
      ->save();

    $account = $this->createUser();
    array_walk($roles, [$account, 'addRole']);
    $account->save();

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    foreach ($destination_map as $destination) {
      list ($destination_parameter, $expected_destination_after_login) = str_replace('{uid}', $account->id(), $destination);

      $options = [];
      if ($destination_parameter) {
        $options['query']['destination'] = $destination_parameter;
      }
      $this->drupalGet('/user/login', $options);
      $page->fillField('name', $account->getAccountName());
      $page->fillField('pass', $account->passRaw);
      $page->pressButton('Log in');
      $assert_session->statusCodeEquals(200);
      $assert_session->addressEquals($expected_destination_after_login);
      $this->drupalLogout();
    }
  }

  /**
   * Data provider for ::testLoginDestination().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerLoginDestination() : array {
    return [
      'content author with redirect' => [
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
      'content editor with redirect' => [
        TRUE,
        [
          ['', '/user/{uid}/moderation/dashboard'],
          ['user', '/user/{uid}/moderation/dashboard'],
          ['/user', '/user/{uid}/moderation/dashboard'],
          ['/user/', '/user/{uid}/moderation/dashboard'],
          ['/user/{uid}', '/user/{uid}/moderation/dashboard'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/admin/content', '/admin/content'],
        ],
        ['content_editor'],
      ],
      'content administrator with redirect' => [
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
      'administrator with redirect' => [
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
      'site builder with redirect' => [
        TRUE,
        [
          ['', '/admin/cohesion'],
          ['user', '/admin/cohesion'],
          ['/user', '/admin/cohesion'],
          ['/user/', '/admin/cohesion'],
          ['/user/{uid}', '/admin/cohesion'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/admin/cohesion', '/admin/cohesion'],
        ],
        ['site_builder'],
      ],
      'developer with redirect' => [
        TRUE,
        [
          ['', '/admin/cohesion'],
          ['user', '/admin/cohesion'],
          ['/user', '/admin/cohesion'],
          ['/user/', '/admin/cohesion'],
          ['/user/{uid}', '/admin/cohesion'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/admin/cohesion', '/admin/cohesion'],
        ],
        ['developer'],
      ],
      'user administrator with redirect' => [
        TRUE,
        [
          ['', '/admin/people'],
          ['user', '/admin/people'],
          ['/user', '/admin/people'],
          ['/user/', '/admin/people'],
          ['/user/{uid}', '/admin/people'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/admin/people', '/admin/people'],
        ],
        ['user_administrator'],
      ],
      'content author+site builder with redirect' => [
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
      'content author+user administrator with redirect' => [
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
      'site builder+user administrator with redirect' => [
        TRUE,
        [
          ['', '/admin/cohesion'],
          ['user', '/admin/cohesion'],
          ['/user', '/admin/cohesion'],
          ['/user/', '/admin/cohesion'],
          ['/user/{uid}', '/admin/cohesion'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/admin/cohesion', '/admin/cohesion'],
        ],
        ['site_builder', 'user_administrator'],
      ],
      'content author+site builder+user administrator with redirect' => [
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
      'site builder without redirect' => [
        FALSE,
        [
          ['', '/user/{uid}'],
          ['user', '/user/{uid}'],
          ['/user', '/user/{uid}'],
          ['/user/', '/user/{uid}'],
          ['/user/{uid}', '/user/{uid}'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/admin/cohesion', '/admin/cohesion'],
        ],
        ['site_builder'],
      ],
      'developer without redirect' => [
        FALSE,
        [
          ['', '/user/{uid}'],
          ['user', '/user/{uid}'],
          ['/user', '/user/{uid}'],
          ['/user/', '/user/{uid}'],
          ['/user/{uid}', '/user/{uid}'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/admin/cohesion', '/admin/cohesion'],
        ],
        ['developer'],
      ],
      'user administrator without redirect' => [
        FALSE,
        [
          ['', '/user/{uid}'],
          ['user', '/user/{uid}'],
          ['/user', '/user/{uid}'],
          ['/user/', '/user/{uid}'],
          ['/user/{uid}', '/user/{uid}'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/admin/people', '/admin/people'],
        ],
        ['user_administrator'],
      ],
      'content administrator without redirect' => [
        FALSE,
        [
          ['', '/user/{uid}/moderation/dashboard'],
          ['user', '/user/{uid}'],
          ['/user', '/user/{uid}'],
          ['/user/', '/user/{uid}'],
          ['/user/{uid}', '/user/{uid}'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/admin/content', '/admin/content'],
        ],
        ['content_administrator'],
      ],
      'content author without redirect' => [
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
      'content editor without redirect' => [
        FALSE,
        [
          ['', '/user/{uid}/moderation/dashboard'],
          ['user', '/user/{uid}'],
          ['/user', '/user/{uid}'],
          ['/user/', '/user/{uid}'],
          ['/user/{uid}', '/user/{uid}'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/admin/content', '/admin/content'],
        ],
        ['content_editor'],
      ],
      'administrator without redirect' => [
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
      'content author+site builder without redirect' => [
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
      'content author+user administrator without redirect' => [
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
      'site builder+user administrator without redirect' => [
        FALSE,
        [
          ['', '/user/{uid}'],
          ['user', '/user/{uid}'],
          ['/user', '/user/{uid}'],
          ['/user/', '/user/{uid}'],
          ['/user/{uid}', '/user/{uid}'],
          ['/user/{uid}/edit', '/user/{uid}/edit'],
          ['/user-stories', '/user-stories'],
          ['/admin/cohesion', '/admin/cohesion'],
        ],
        ['site_builder', 'user_administrator'],
      ],
      'content author+site builder+user administrator without redirect' => [
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
