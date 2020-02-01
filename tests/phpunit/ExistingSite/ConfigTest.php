<?php

namespace Drupal\Tests\acquia_cms\ExistingSite;

use Drupal\user\UserInterface;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests config values that are set at install.
 *
 * @group acquia_cms
 */
class ConfigTest extends ExistingSiteBase {

  /**
   * Tests config set during install.
   */
  public function testConfig() {

    // Assert that all install tasks have done what they should do.
    // @see acquia_cms_install_tasks()
    $account = \Drupal::entityTypeManager()
       ->getStorage('user')
       ->load(1);
    $this->assertInstanceOf(UserInterface::class, $account);
    /** @var \Drupal\user\UserInterface $account */
    $this->assertTrue($account->hasRole('administrator'));

    $theme_config = $this->config('system.theme');
    $this->assertSame('cohesion_theme', $theme_config->get('default'));
    $this->assertSame('claro', $theme_config->get('admin'));

    $cohesion_config = $this->config('cohesion.settings');
    $this->assertSame($_ENV['COHESION_API_KEY'], $cohesion_config->get('api_key'));
    $this->assertSame($_ENV['COHESION_ORG_KEY'], $cohesion_config->get('organization_key'));
  }

  /**
   * Returns a config object by its name.
   *
   * @param string $name
   *   The name of the config object to return.
   *
   * @return \Drupal\Core\Config\Config
   *   The config object.
   */
  private function config($name) {
    return $this->container->get('config.factory')->getEditable($name);
  }

}
