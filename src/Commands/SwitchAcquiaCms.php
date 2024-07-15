<?php

namespace Drupal\acquia_cms\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 */
class SwitchAcquiaCms extends DrushCommands {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Key Value Factory service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyvalue;

  /**
   * The State service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a ModuleHandlerInterface object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $keyvalue
   *   The Key Value Factory service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory,
    KeyValueFactoryInterface $keyvalue,
    StateInterface $state,
  ) {
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->keyvalue = $keyvalue;
    $this->state = $state;
  }

  /**
   * Switch profile.
   *
   * @command acms:switch
   * @aliases acms-switch
   */
  public function switchAcmsToMinimal($profile_to_install = "minimal") {
    $profile_to_remove = $this->configFactory->getEditable('core.extension')->get('profile');
    if ($profile_to_remove == 'acquia_cms') {
      $this->output()->writeln(dt("The site's install profile will be switched from !profile_to_remove to !profile_to_install.", [
        '!profile_to_remove' => $profile_to_remove,
        '!profile_to_install' => $profile_to_install,
      ]));
      if (!$this->io()->confirm(dt('Do you want to continue?'))) {
        throw new UserAbortException();
      }
      // Forces ExtensionDiscovery to rerun for profiles.
      $this->state->delete('system.profile.files');

      // Set the profile in configuration.
      $extension_config = $this->configFactory->getEditable('core.extension');
      $extension_config->set('profile', $profile_to_install)
        ->save();

      drupal_flush_all_caches();

      // Install profiles are also registered as enabled modules.
      // Remove the old profile and add in the new one.
      $extension_config->clear("module.{$profile_to_remove}")
        ->save();
      // The install profile is always given a weight of 1000 by the core
      // extension system.
      $extension_config->set("module.$profile_to_install", 1000)
        ->save();

      // Remove the schema value for the old install profile, and set the schema
      // for the new one. We set the schema version to 8000, in the absence of
      // any knowledge about it. TODO: add an option for the schema version to
      // set for the new profile, or better yet, analyse the profile's
      // hook_update_N()functions to deduce the schema to set.
      $this->keyvalue->get('system.schema')->delete($profile_to_remove);
      $this->keyvalue->get('system.schema')->set($profile_to_install, 8000);

      // Clear caches again.
      drupal_flush_all_caches();
      $this->output()->writeln(dt("Profile switched from !profile_to_remove to !profile_to_install.", [
        '!profile_to_remove' => $profile_to_remove,
        '!profile_to_install' => $this->configFactory->getEditable('core.extension')->get('profile'),
      ]));
      // Update logo and favicon path.
      $acquia_cms_common_path = $this->moduleHandler->getModule('acquia_cms_common')->getPath();
      // Update favicon path.
      if ($this->configFactory->getEditable('system.theme.global')->get('favicon.path') == 'profiles/contrib/acquia_cms/acquia_cms.png') {
        $this->configFactory->getEditable('system.theme.global')
          ->set('favicon.path', '/' . $acquia_cms_common_path . '/acquia_cms.png')
          ->save();
      }
      // Update logo path.
      if ($this->configFactory->getEditable('system.theme.global')->get('logo.path') == 'profiles/contrib/acquia_cms/acquia_cms.png') {
        $this->configFactory->getEditable('system.theme.global')
          ->set('logo.path', '/' . $acquia_cms_common_path . '/acquia_cms.png')
          ->save();
      }
      // Clear caches.
      drupal_flush_all_caches();
    }
    else {
      $this->output->writeln("You are not running on Acquia CMS profile.");
    }
  }

}
