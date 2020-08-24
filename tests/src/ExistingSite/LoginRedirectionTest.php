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
        '/user/{uid}/moderation/dashboard',
        ['site_builder', 'user_administrator'],
      ],
      'content author+site builder+user administrator without redirect' => [
        FALSE,
        '/user/{uid}/moderation/dashboard',
        ['content_author', 'site_builder', 'user_administrator'],
      ],
    ];
  }

}
