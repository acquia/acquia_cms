<?php

namespace Drupal\acquia_cms\Composer;

use Composer\Json\JsonFile;
use Composer\Script\Event;

/**
 * Configures an instance of drupal/legacy-project to install Acquia CMS.
 */
final class ConfigureProject {

  /**
   * Executes the script.
   *
   * @param \Composer\Script\Event $event
   *   The script event.
   */
  public static function execute(Event $event) {
    $arguments = $event->getArguments();

    $target = new JsonFile($arguments[0] . '/composer.json');
    $project = $target->read();

    $project['name'] = 'acquia/acquia-cms-project';
    $project['extra']['installer-paths']['libraries/{$name}'] = [
      'type:drupal-library',
      'type:bower-asset',
      'type:npm-asset',
    ];
    $project['extra']['installer-types'] = ['bower-asset', 'npm-asset'];
    $project['extra']['patchLevel']['drupal/core'] = '-p2';
    $project['extra']['enable-patching'] = TRUE;

    // Composer doesn't like empty sections of composer.json, so
    // filter those out before we write the configuration.
    $project = array_filter($project);
    $target->write($project);
  }

}
