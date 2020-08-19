<?php

namespace Drupal\Tests\acquia_cms\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests Login redirection.
 *
 * @group acquia_cms
 */
class LoginRedirectionTest extends ExistingSiteBase {

  /**
   * Test the redirection when configuration is 'ON'.
   */
  public function testLoginRedirectionOnConfig() {
    $config = $this->setConfig('ON');
    if ($config->get('user_login_redirection') === 'ON') {
      $account = $this->createUser();
      $account->addRole('content_author');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/{$account->id()}/moderation/dashboard");

      $account = $this->createUser();
      $account->addRole('content_editor');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/{$account->id()}/moderation/dashboard");

      $account = $this->createUser();
      $account->addRole('content_administrator');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/{$account->id()}/moderation/dashboard");

      $account = $this->createUser();
      $account->addRole('administrator');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/{$account->id()}/moderation/dashboard");

      $account = $this->createUser();
      $account->addRole('site_builder');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/admin/cohesion");

      $account = $this->createUser();
      $account->addRole('developer');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/admin/cohesion");

      $account = $this->createUser();
      $account->addRole('user_administrator');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/admin/people");

      $account = $this->createUser();
      $account->addRole('content_author');
      $account->addRole('site_builder');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/{$account->id()}/moderation/dashboard");

      $account = $this->createUser();
      $account->addRole('content_author');
      $account->addRole('user_administrator');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/{$account->id()}/moderation/dashboard");

      $account = $this->createUser();
      $account->addRole('site_builder');
      $account->addRole('user_administrator');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/admin/cohesion");

      $account = $this->createUser();
      $account->addRole('content_author');
      $account->addRole('site_builder');
      $account->addRole('user_administrator');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/{$account->id()}/moderation/dashboard");
    }
  }

  /**
   * Test the redirection when configuration is 'OFF'.
   */
  public function testLoginRedirectionOffConfig() {
    $config = $this->setConfig('OFF');
    if ($config->get('user_login_redirection') === 'OFF') {
      $account = $this->createUser();
      $account->addRole('site_builder');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/{$account->id()}");

      $account = $this->createUser();
      $account->addRole('developer');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/{$account->id()}");

      $account = $this->createUser();
      $account->addRole('user_administrator');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/{$account->id()}");

      $account = $this->createUser();
      $account->addRole('content_administrator');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/{$account->id()}/moderation/dashboard");

      $account = $this->createUser();
      $account->addRole('content_author');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/{$account->id()}/moderation/dashboard");

      $account = $this->createUser();
      $account->addRole('content_editor');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/{$account->id()}/moderation/dashboard");

      $account = $this->createUser();
      $account->addRole('administrator');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/{$account->id()}/moderation/dashboard");

      $account = $this->createUser();
      $account->addRole('content_author');
      $account->addRole('site_builder');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/{$account->id()}/moderation/dashboard");

      $account = $this->createUser();
      $account->addRole('content_author');
      $account->addRole('user_administrator');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/{$account->id()}/moderation/dashboard");

      $account = $this->createUser();
      $account->addRole('site_builder');
      $account->addRole('user_administrator');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/{$account->id()}");

      $account = $this->createUser();
      $account->addRole('content_author');
      $account->addRole('site_builder');
      $account->addRole('user_administrator');
      $account->save();
      $this->drupalLogin($account);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/{$account->id()}/moderation/dashboard");
    }
  }

  /**
   * Set the configuration and returns a config object.
   *
   * @param string $config_setting
   *   The config setting.
   *
   * @return object
   *   The config object.
   */
  private function setConfig(string $config_setting) {
    $config = $this->container->get('config.factory')
      ->getEditable('acquia_cms.settings');
    $config->set('user_login_redirection', $config_setting);
    $config->save();
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->setConfig('ON');
    parent::tearDown();
  }

}
