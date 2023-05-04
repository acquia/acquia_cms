<?php

namespace Drupal\Tests\acquia_post_config_events_test\Functional;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Database\Database;
use Drupal\FunctionalTests\Installer\InstallerExistingConfigTestBase;
use Drupal\FunctionalTests\Installer\InstallerTestBase;

/**
 * Tests the Site Install with existing config.
 */
abstract class ExistingSiteInstallBase extends InstallerExistingConfigTestBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    InstallerTestBase::prepareEnvironment();
    $archiver = new ArchiveTar($this->getConfigTarball(), 'gz');

    if ($this->profile === NULL) {
      $core_extension = Yaml::decode($archiver->extractInString('./core.extension.yml'));
      $this->profile = $core_extension['profile'];
    }

    // Create a profile for testing. We set core_version_requirement to '*' for
    // the test so that it does not need to be updated between major versions.
    $info = [
      'type' => 'profile',
      'core_version_requirement' => '*',
      'name' => 'Configuration installation test profile (' . $this->profile . ')',
    ];

    // File API functions are not available yet.
    $path = $this->siteDirectory . '/profiles/' . $this->profile;
    if ($this->existingSyncDirectory) {
      $config_sync_directory = $this->siteDirectory . '/config/sync';
      $this->settings['settings']['config_sync_directory'] = (object) [
        'value' => $config_sync_directory,
        'required' => TRUE,
      ];
    }
    else {
      // Put the sync directory inside the profile.
      $config_sync_directory = $path . '/config/sync';
    }

    mkdir($path, 0777, TRUE);
    file_put_contents("$path/{$this->profile}.info.yml", Yaml::encode($info));

    // Create config/sync directory and extract tarball contents to it.
    mkdir($config_sync_directory, 0777, TRUE);
    $files = [];
    $list = $archiver->listContent();
    if (is_array($list)) {
      /** @var array $list */
      foreach ($list as $file) {
        $files[] = $file['filename'];
      }
      $archiver->extractList($files, $config_sync_directory);
    }

    // Add the module that is providing the database driver to the list of
    // modules that can not be uninstalled in the core.extension configuration.
    if (file_exists($config_sync_directory . '/core.extension.yml')) {
      $core_extension = Yaml::decode(file_get_contents($config_sync_directory . '/core.extension.yml'));
      $module = Database::getConnection()->getProvider();
      if ($module !== 'core') {
        $core_extension['module'][$module] = 0;
        $core_extension['module'] = module_config_sort($core_extension['module']);
        file_put_contents($config_sync_directory . '/core.extension.yml', Yaml::encode($core_extension));
      }
    }
  }

}
