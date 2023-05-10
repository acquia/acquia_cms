<?php

namespace Acquia\Drupal\RecommendedSettings;

use Acquia\Drupal\RecommendedSettings\Common\RandomString;
use Acquia\Drupal\RecommendedSettings\Config\ConfigInitializer;
use Acquia\Drupal\RecommendedSettings\Config\SettingsConfig;
use Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * Core class of the plugin.
 *
 * Contains the primary logic to copy acquia-recommended-settings files.
 *
 * @internal
 */
class Settings {

  /**
   * Settings warning.
   *
   * @var string
   * Warning text added to the end of settings.php to point people to the BLT
   * docs on how to include settings.
   */
  private $settingsWarning = <<<WARNING
/**
 * IMPORTANT.
 *
 * Do not include additional settings here. Instead, add them to settings
 * included by `acquia-recommended.settings.php`. See Acquia's documentation for more detail.
 *
 * @link https://docs.acquia.com/
 */
WARNING;

  /**
   * The repo root path.
   *
   * @var string
   */
  protected string $rootPath;

  /**
   * The composer object.
   *
   * @var \Composer\Composer
   */
  protected Composer $composer;

  /**
   * The symfony input-output object.
   *
   * @var \Composer\IO\IOInterface
   */
  protected IOInterface $io;

  /**
   * The symfony file-system object.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected SymfonyFilesystem $fileSystem;

  /**
   * The settings package object.
   *
   * @var \Composer\Package\PackageInterface
   */
  protected PackageInterface $settingsPackage;

  /**
   * The site name.
   *
   * @var string
   */
  protected $siteName;

  /**
   * The site name.
   *
   * @var string
   */
  protected $getWebRootPath;

  /**
   * Constructs the plugin object.
   */
  public function __construct(Composer $composer, IOInterface $io, PackageInterface $package) {
    $this->composer = $composer;
    $this->io = $io;
    $this->fileSystem = new SymfonyFilesystem();
    $this->settingsPackage = $package;
    $this->siteName = 'default';
    $this->getWebRootPath = $this->getWebRootPath();
  }

  /**
   * Writes a hash salt to ${repo.root}/salt.txt if one does not exist.
   *
   * @command drupal:hash-salt:init
   * @aliases dhsi setup:hash-salt
   *
   * @return int
   *   A CLI exit code.
   *
   * @throws \Acquia\Drupal\RecommendedSettings\Exceptions\SettingsException
   */
  public function hashSalt(): int {
    $hash_salt_file = $this->getRootPath() . '/salt.txt';
    if (!$this->fileSystem->exists($hash_salt_file)) {
      $this->io->write("<info>Generating hash salt...</info>");
      $this->fileSystem->appendToFile($hash_salt_file, RandomString::string(55));
      if (!is_writable($hash_salt_file)) {
        throw new SettingsException(sprintf("Can not create file. File `%s` is not writable.", $hash_salt_file));
      }
    }
    else {
      $this->io->write("<comment>Hash salt already exists.</comment>");
    }
    return 0;
  }

  /**
   * Get the settings files.
   *
   * @return array
   *   A settings files.
   */
  public function getSettings(): array {
    // Generate settings.php.
    $settings[$this->siteName] = $this->getWebRootPath . "/sites/default";
    $settings['project_default_settings_file'] = $settings[$this->siteName] . "/default.settings.php";
    $settings['project_settings_file'] = $settings[$this->siteName] . "/settings.php";

    // Generate local.settings.php.
    $settings['recommended_local_settings_file'] = $this->getSettingsPackagePath() . '/settings/default.local.settings.php';
    $settings['default_local_settings_file'] = $settings[$this->siteName] . "/settings/default.local.settings.php";
    $settings['project_local_settings_file'] = $settings[$this->siteName] . "/settings/local.settings.php";

    // Generate default.includes.settings.php.
    $settings['recommended_includes_settings_file'] = $this->getSettingsPackagePath() . '/settings/default.includes.settings.php';
    $settings['default_includes_settings_file'] = $settings[$this->siteName] . "/settings/default.includes.settings.php";

    // Generate sites/settings/default.global.settings.php.
    $settings['recommended_glob_settings_file'] = $this->getSettingsPackagePath() . '/settings/default.global.settings.php';
    $settings['default_glob_settings_file'] = $this->getWebRootPath . "/sites/settings/default.global.settings.php";
    $settings['global_settings_file'] = $this->getWebRootPath . "/sites/settings/global.settings.php";

    // Generate local.drush.yml.
    // phpcs:ignore
    // $settings['recommended_local_drush_file'] = $this->getSettingsPackagePath() . '/settings/default.local.drush.yml';
    // phpcs:ignore
    // $settings['default_local_drush_file'] = $settings[$this->siteName] . "/default.local.drush.yml";
    // phpcs:ignore
    // $settings['project_local_drush_file'] = $settings[$this->siteName] . "/local.drush.yml";.
    return $settings;
  }

  /**
   * Generate the settings files.
   */
  public function generateSettings(): void {
    $setting_files = $this->getSettings();

    $copy_map = [
      $setting_files['recommended_local_settings_file'] => $setting_files['default_local_settings_file'],
      $setting_files['default_local_settings_file'] => $setting_files['project_local_settings_file'],
      $setting_files['recommended_includes_settings_file'] => $setting_files['default_includes_settings_file'],
    ];
    // Define an array of files that require property expansion.
    // phpcs:ignore
    // $expand_map = [$settings['default_local_settings_file'] => $settings['project_local_settings_file']];

    // Add default.global.settings.php if global.settings.php does not exist.
    if (!$this->fileSystem->exists($setting_files['global_settings_file'])) {
      $copy_map[$setting_files['recommended_glob_settings_file']] = $setting_files['default_glob_settings_file'];
    }

    // Only add the settings file if the default exists.
    if (file_exists($setting_files['project_default_settings_file'])) {
      $copy_map[$setting_files['project_default_settings_file']] = $setting_files['project_settings_file'];
    }
    elseif (!file_exists($setting_files['project_default_settings_file'])) {
      $this->io->write("<comment>No " . $setting_files['project_default_settings_file'] . " file found.</comment>");
    }

    $this->fileSystem->chmod($setting_files[$this->siteName], 0755, 0000, TRUE);

    $config = new ConfigInitializer($this->getRootPath(), $this->getWebRootPath, $this->getVendorPath() . "/" . $this->settingsPackage->getName());
    $config = $config->initialize();
    $settings = new SettingsConfig($config->export());

    // Copy files without overwriting.
    foreach ($copy_map as $from => $to) {
      if (!$this->fileSystem->exists($to)) {
        $this->fileSystem->copy($from, $to);
        $settings->expandFileProperties($to);
      }
    }
    $this->appendIfMatchesCollect($setting_files['project_settings_file'], '#vendor/acquia/drupal-recommended-settings/settings/acquia-recommended.settings.php#', 'require DRUPAL_ROOT . "/../vendor/acquia/drupal-recommended-settings/settings/acquia-recommended.settings.php";' . "\n");
    $this->appendIfMatchesCollect($setting_files['project_settings_file'], '#Do not include additional settings here#', $this->settingsWarning . "\n");
  }

  /**
   * Gets the path to the 'vendor' directory.
   *
   * @return string
   *   The file path of the vendor directory.
   */
  protected function getVendorPath(): string {
    $vendor_dir = $this->composer->getConfig()->get('vendor-dir');
    $filesystem = new Filesystem();
    return $filesystem->normalizePath(realpath($vendor_dir));
  }

  /**
   * Gets the root path to project.
   */
  protected function getRootPath(): string {
    return dirname($this->getVendorPath());
  }

  /**
   * Gets the path to the 'vendor' directory.
   */
  protected function getWebRootPath(): string {
    $extra = $this->composer->getPackage()->getExtra();
    $webRoot = $extra['drupal-scaffold']['locations']['web-root'] ?? ".";
    $webRoot = $this->getRootPath() . "/" . $webRoot;
    $filesystem = new Filesystem();
    return $filesystem->normalizePath(realpath($webRoot));
  }

  /**
   * Gets the path to package.
   */
  protected function getSettingsPackagePath(): string {
    return $this->composer->getInstallationManager()->getInstallPath($this->settingsPackage);
  }

  /**
   * Append the string to file, if matches.
   *
   * @param string $file
   *   The path to file.
   * @param string $pattern
   *   The regex patten.
   * @param string $text
   *   Text to append.
   * @param bool $shouldMatch
   *   Decides when to append if match found.
   */
  protected function appendIfMatchesCollect(string $file, string $pattern, string $text, bool $shouldMatch = FALSE): void {
    $contents = file_get_contents($file);
    if (preg_match($pattern, $contents) == $shouldMatch) {
      $contents .= $text;
    }
    $this->fileSystem->dumpFile($file, $contents);
  }

}
