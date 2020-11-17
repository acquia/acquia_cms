<?php

namespace Drupal\Tests\acquia_cms\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests HTTPS redirection.
 *
 * @group acquia_cms
 */
class HttpsRedirectTest extends ExistingSiteBase {

  /**
   * Status of the HTTPS redirect configuration.
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
      ->get('acquia_cms_https');
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->container->get('config.factory')
      ->getEditable('acquia_cms.settings')
      ->set('acquia_cms_https', $this->enabled)
      ->save();
    parent::tearDown();
  }

  /**
   * Tests enforced HTTPS redirect.
   *
   * @param string[]|null $roles
   *   The user role(s) to test with, or NULL to test as an anonymous user. If
   *   this is an empty array, the test will run as an authenticated user with
   *   no additional roles.
   * @param int $status_code
   *   The expected status code of the page.
   *
   * @dataProvider providerHttpsRedirect
   */
  public function testHttpsRedirect(?array $roles, int $status_code) : void {
    if (isset($roles)) {
      $account = $this->createUser();
      array_walk($roles, [$account, 'addRole']);
      $account->save();
      $this->drupalLogin($account);
    }

    $session = $this->getSession();
    // Assert that Https enformed config form is accessible or not.
    $this->drupalGet('/admin/config/system/https');
    $current_status_code = $session->getStatusCode();
    $this->assertFalse($this->assertIsHttps());
    $this->assertSame($current_status_code, $status_code);

    // Enabling HTTPS redirect.
    $this->container->get('config.factory')
      ->getEditable('acquia_cms.settings')
      ->set('acquia_cms_https', 1)
      ->save();

    $this->drupalGet('/node');
    $current_status_code = $session->getStatusCode();
    $this->assertTrue($this->assertIsHttps());
  }

  /**
   * Assert whether the request is of type HTTP or HTTPS.
   */
  private function assertIsHttps() {
    $session = $this->getSession();
    $server = $session->getDriver()
      ->getClient()
      ->getInternalRequest()
      ->getServer();

    return $server['HTTPS'];
  }

  /**
   * Data provider for ::testHttpsRedirect().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerHttpsRedirect() {
    return [
      'anonymous user' => [
        NULL,
        403,
      ],
      'authenticated user' => [
        [],
        403,
      ],
      'content author' => [
        ['content_author'],
        403,
      ],
      'content editor' => [
        ['content_editor'],
        403,
      ],
      'content administrator' => [
        ['content_administrator'],
        403,
      ],
      'administrator' => [
        ['administrator'],
        200,
      ],
    ];
  }

}
