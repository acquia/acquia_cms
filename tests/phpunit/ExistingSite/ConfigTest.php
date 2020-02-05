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
   * Assert that all install tasks have done what they should do.
   *
   * See acquia_cms_install_tasks().
   */
  public function testConfig() {

    // Check that the admin role has been created, and that user 1
    // is set as an admin.
    $account = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->load(1);
    $this->assertInstanceOf(UserInterface::class, $account);
    /** @var \Drupal\user\UserInterface $account */
    $this->assertTrue($account->hasRole('administrator'));

    // Check that the default and admin themes are set as expected.
    $theme_config = $this->config('system.theme');
    $this->assertSame('cohesion_theme', $theme_config->get('default'));
    $this->assertSame('claro', $theme_config->get('admin'));

    // Check that the node create form is using the admin theme.
    $node_form_config = $this->config('node.settings');
    $this->assertSame(TRUE, $node_form_config->get('use_admin_theme'));

    // Check that the Cohesion API keys are set.
    $cohesion_config = $this->config('cohesion.settings');
    $this->assertSame(getenv('COHESION_API_KEY'), $cohesion_config->get('api_key'));
    $this->assertSame(getenv('COHESION_ORG_KEY'), $cohesion_config->get('organization_key'));
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
