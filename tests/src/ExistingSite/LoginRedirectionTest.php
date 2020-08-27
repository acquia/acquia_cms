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
   * @param bool $enable
   *   Whether or not to enable special redirect handling.
   * @param string $destination
   *   The expected destination upon logging in.
   * @param string[] $roles
   *   Additional user roles to apply to the account being logged in.
   *
   * @dataProvider providerLoginDestination
   */
  public function testLoginDestination(bool $enable, string $destination, array $roles = []) : void {
    $this->container->get('config.factory')
      ->getEditable('acquia_cms.settings')
      ->set('user_login_redirection', $enable)
      ->save();

    $account = $this->createUser();
    array_walk($roles, [$account, 'addRole']);
    $account->save();
    $this->drupalLogin($account);

    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);
    $destination = str_replace('{uid}', $account->id(), $destination);
    $assert_session->addressEquals($destination);
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
        '/user/{uid}/moderation/dashboard',
        ['content_author'],
      ],
      'content editor with redirect' => [
        TRUE,
        '/user/{uid}/moderation/dashboard',
        ['content_editor'],
      ],
      'content administrator with redirect' => [
        TRUE,
        '/user/{uid}/moderation/dashboard',
        ['content_administrator'],
      ],
      'administrator with redirect' => [
        TRUE,
        '/user/{uid}/moderation/dashboard',
        ['administrator'],
      ],
      'site builder with redirect' => [
        TRUE,
        '/admin/cohesion',
        ['site_builder'],
      ],
      'developer with redirect' => [
        TRUE,
        '/admin/cohesion',
        ['developer'],
      ],
      'user administrator with redirect' => [
        TRUE,
        '/admin/people',
        ['user_administrator'],
      ],
      'content author+site builder with redirect' => [
        TRUE,
        '/user/{uid}/moderation/dashboard',
        ['content_author', 'site_builder'],
      ],
      'content author+user administrator with redirect' => [
        TRUE,
        '/user/{uid}/moderation/dashboard',
        ['content_author', 'user_administrator'],
      ],
      'site builder+user administrator with redirect' => [
        TRUE,
        '/admin/cohesion',
        ['site_builder', 'user_administrator'],
      ],
      'content author+site builder+user administrator with redirect' => [
        TRUE,
        '/user/{uid}/moderation/dashboard',
        ['content_author', 'site_builder', 'user_administrator'],
      ],
      'site builder without redirect' => [
        FALSE,
        '/user/{uid}',
        ['site_builder'],
      ],
      'developer without redirect' => [
        FALSE,
        '/user/{uid}',
        ['developer'],
      ],
      'user administrator without redirect' => [
        FALSE,
        '/user/{uid}',
        ['user_administrator'],
      ],
      'content administrator without redirect' => [
        FALSE,
        '/user/{uid}/moderation/dashboard',
        ['content_administrator'],
      ],
      'content author without redirect' => [
        FALSE,
        '/user/{uid}/moderation/dashboard',
        ['content_author'],
      ],
      'content editor without redirect' => [
        FALSE,
        '/user/{uid}/moderation/dashboard',
        ['content_editor'],
      ],
      'administrator without redirect' => [
        FALSE,
        '/user/{uid}/moderation/dashboard',
        ['administrator'],
      ],
      'content author+site builder without redirect' => [
        FALSE,
        '/user/{uid}/moderation/dashboard',
        ['content_author', 'site_builder'],
      ],
      'content author+user administrator without redirect' => [
        FALSE,
        '/user/{uid}/moderation/dashboard',
        ['content_author', 'user_administrator'],
      ],
      'site builder+user administrator without redirect' => [
        FALSE,
        '/user/{uid}',
        ['site_builder', 'user_administrator'],
      ],
      'content author+site builder+user administrator without redirect' => [
        FALSE,
        '/user/{uid}/moderation/dashboard',
        ['content_author', 'site_builder', 'user_administrator'],
      ],
    ];
  }

  /**
   * Tests special redirect handling upon user login with destination.
   *
   * @param bool $enable
   *   Whether or not to enable special redirect handling.
   * @param string $role
   *   Additional user roles to apply to the account being logged in.
   * @param string[] $destination
   *   The expected destination upon logging in.
   * @param string[] $query
   *   The expected query parameter.
   *
   * @dataProvider providerDestinationParameter
   */
  public function testDestinationParameter(bool $enable, string $role, array $destination = [], array $query = []) : void {
    $this->container->get('config.factory')
      ->getEditable('acquia_cms.settings')
      ->set('user_login_redirection', $enable)
      ->save();

    $account = $this->createUser();
    $account->addRole($role);
    $account->save();

    array_walk ($query, function ($value, $key, $params) {
      $destination = str_replace('{uid}', $params[1]->id(), $params[0][$key]);
      $query = str_replace('{uid}', $params[1]->id(), $value);
      $edit = [
        'name' => $params[1]->getAccountName(),
        'pass' => $params[1]->passRaw,
      ];
      $this->drupalPostForm(Url::fromRoute('user.login'), $edit, $this->t('Log in'), ['query' => ['destination' => $query]]);
      $this->assertSession()->addressEquals($destination);
      $this->drupalLogout();
    }, [$destination, $account]);
  }

  /**
   * Data provider for ::testDestinationParameter().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerDestinationParameter() : array {
    return [
      'content author with redirect and user destination' => [
        TRUE,
        'content_author',
        ['/user/{uid}/moderation/dashboard', '/user/{uid}/moderation/dashboard', '/user/{uid}/moderation/dashboard', '/user/{uid}/moderation/dashboard', '/user/{uid}/moderation/dashboard'],
        ['user', '/user', '/user/', '/user/{uid}', ''],
      ],
      'content author with redirect' => [
        TRUE,
        'content_author',
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
      ],
      'content editor with redirect and user destination' => [
        TRUE,
        'content_editor',
        ['/user/{uid}/moderation/dashboard', '/user/{uid}/moderation/dashboard', '/user/{uid}/moderation/dashboard', '/user/{uid}/moderation/dashboard', '/user/{uid}/moderation/dashboard'],
        ['user', '/user', '/user/', '/user/{uid}', ''],
      ],
      'content editor with redirect' => [
        TRUE,
        'content_editor',
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
      ],
      'content administrator with redirect and user destination' => [
        TRUE,
        'content_administrator',
        ['/user/{uid}/moderation/dashboard', '/user/{uid}/moderation/dashboard', '/user/{uid}/moderation/dashboard', '/user/{uid}/moderation/dashboard', '/user/{uid}/moderation/dashboard'],
        ['user', '/user', '/user/', '/user/{uid}', ''],
      ],
      'content administrator with redirect' => [
        TRUE,
        'content_administrator',
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
      ],
      'administrator with redirect and user destination' => [
        TRUE,
        'administrator',
        ['/user/{uid}/moderation/dashboard', '/user/{uid}/moderation/dashboard', '/user/{uid}/moderation/dashboard', '/user/{uid}/moderation/dashboard', '/user/{uid}/moderation/dashboard'],
        ['user', '/user', '/user/', '/user/{uid}', ''],
      ],
      'administrator with redirect' => [
        TRUE,
        'administrator',
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
      ],
      'site builder with redirect and user destination' => [
        TRUE,
        'site_builder',
        ['/admin/cohesion', '/admin/cohesion', '/admin/cohesion', '/admin/cohesion', '/admin/cohesion'],
        ['user', '/user', '/user/', '/user/{uid}', ''],
      ],
      'site builder with redirect' => [
        TRUE,
        'site_builder',
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
      ],
      'developer with redirect and user destination' => [
        TRUE,
        'developer',
        ['/admin/cohesion', '/admin/cohesion', '/admin/cohesion', '/admin/cohesion', '/admin/cohesion'],
        ['user', '/user', '/user/', '/user/{uid}', ''],
      ],
      'developer with redirect' => [
        TRUE,
        'developer',
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
      ],
      'user administrator with redirect and user destination' => [
        TRUE,
        'user_administrator',
        ['/admin/people', '/admin/people', '/admin/people', '/admin/people', '/admin/people'],
        ['user', '/user', '/user/', '/user/{uid}', ''],
      ],
      'user administrator with redirect' => [
        TRUE,
        'user_administrator',
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
      ],
      'site builder without redirect and user destination' => [
        FALSE,
        'site_builder',
        ['/user/{uid}', '/user/{uid}', '/user/{uid}', '/user/{uid}', '/user/{uid}'],
        ['user', '/user', '/user/', '/user/{uid}', ''],
      ],
      'site builder without redirect' => [
        FALSE,
        'site_builder',
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
      ],
      'developer without redirect and user destination' => [
        FALSE,
        'developer',
        ['/user/{uid}', '/user/{uid}', '/user/{uid}', '/user/{uid}', '/user/{uid}'],
        ['user', '/user', '/user/', '/user/{uid}', ''],
      ],
      'developer without redirect' => [
        FALSE,
        'developer',
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
      ],
      'user administrator without redirect and user destination' => [
        FALSE,
        'user_administrator',
        ['/user/{uid}', '/user/{uid}', '/user/{uid}', '/user/{uid}', '/user/{uid}'],
        ['user', '/user', '/user/', '/user/{uid}', ''],
      ],
      'user administrator without redirect' => [
        FALSE,
        'user_administrator',
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
      ],
      'content administrator without redirect and user destination' => [
        FALSE,
        'content_administrator',
        ['/user/{uid}', '/user/{uid}', '/user/{uid}', '/user/{uid}', '/user/{uid}/moderation/dashboard'],
        ['user', '/user', '/user/', '/user/{uid}', ''],
      ],
      'content administrator without redirect' => [
        FALSE,
        'content_administrator',
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
      ],
      'content author without redirect and user destination' => [
        FALSE,
        'content_author',
        ['/user/{uid}', '/user/{uid}', '/user/{uid}', '/user/{uid}', '/user/{uid}/moderation/dashboard'],
        ['user', '/user', '/user/', '/user/{uid}', ''],
      ],
      'content author without redirect' => [
        FALSE,
        'content_author',
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
      ],
      'content editor without redirect and user destination' => [
        FALSE,
        'content_editor',
        ['/user/{uid}', '/user/{uid}', '/user/{uid}', '/user/{uid}', '/user/{uid}/moderation/dashboard'],
        ['user', '/user', '/user/', '/user/{uid}', ''],
      ],
      'content editor without redirect' => [
        FALSE,
        'content_editor',
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
      ],
      'administrator without redirect and user destination' => [
        FALSE,
        'administrator',
        ['/user/{uid}', '/user/{uid}', '/user/{uid}', '/user/{uid}', '/user/{uid}/moderation/dashboard'],
        ['user', '/user', '/user/', '/user/{uid}', ''],
      ],
      'administrator without redirect' => [
        FALSE,
        'administrator',
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
        ['/user/{uid}/edit', '/user-stories', '/node/add'],
      ],
    ];
  }

}
