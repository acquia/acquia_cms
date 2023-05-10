<?php

namespace Acquia\Drupal\RecommendedSettings\Config;

use Acquia\Drupal\RecommendedSettings\Helpers\EnvironmentDetector;
use Consolidation\Config\Config;
use Consolidation\Config\Loader\YamlConfigLoader;

/**
 * Config init.
 */
class ConfigInitializer {

  /**
   * Config.
   *
   * @var \Consolidation\Config\Config
   */
  protected $config;

  /**
   * Loader.
   *
   * @var \Consolidation\Config\Loader\YamlConfigLoader
   */
  protected $loader;

  /**
   * Processor.
   *
   * @var \Acquia\Blt\Robo\Config\YamlConfigProcessor
   */
  protected $processor;

  /**
   * Site.
   *
   * @var string
   */
  protected $site;

  /**
   * Environment.
   *
   * @var string
   */
  protected $environment;

  /**
   * Repo root.
   *
   * @var string
   */
  protected $webRoot;

  /**
   * Web root of the project.
   *
   * @var string
   */
  protected $repoRoot;

  /**
   * Path to this project.
   *
   * @var string
   */
  protected $settingsRoot;

  /**
   * ConfigInitializer constructor.
   *
   * @param string $repo_root
   *   Repo root.
   * @param string $web_root
   *   Web root of the project.
   * @param string $settings_root
   *   Path to this project.
   */
  public function __construct(string $repo_root, string $web_root, string $settings_root) {
    $this->webRoot = $web_root;
    $this->repoRoot = $repo_root;
    $this->settingsRoot = $settings_root;
    $this->config = new Config();
    $this->loader = new YamlConfigLoader();
    $this->processor = new YamlConfigProcessor();
  }

  /**
   * Set site.
   *
   * @param mixed $site
   *   Site.
   */
  public function setSite($site): void {
    $this->site = $site;
    $this->config->set('site', $site);
  }

  /**
   * Determine site.
   *
   * @return mixed|string
   *   Site.
   */
  protected function determineSite() {
    return 'default';
  }

  /**
   * Initialize.
   */
  public function initialize(): Config {
    if (!$this->site) {
      $site = $this->determineSite();
      $this->setSite($site);
    }
    $environment = $this->determineEnvironment();
    $this->environment = $environment;
    $this->config->set('environment', $environment);
    $this->loadConfigFiles();
    $this->processConfigFiles();

    return $this->config;
  }

  /**
   * Load config.
   *
   * @return $this
   *   Config.
   */
  public function loadConfigFiles(): ConfigInitializer {
    $this->loadDefaultConfig();
    // $this->loadProjectConfig();
    $this->loadSiteConfig();

    return $this;
  }

  /**
   * Load config.
   *
   * @return $this
   *   Config.
   */
  public function loadDefaultConfig(): ConfigInitializer {
    $this->processor->add($this->config->export());
    $this->processor->extend($this->loader->load($this->settingsRoot . '/config/build.yml'));
    return $this;
  }

  /**
   * Load config.
   *
   * @return $this
   *   Config.
   */
  public function loadSiteConfig(): ConfigInitializer {
    if ($this->site) {
      // Since docroot can change in the project, we need to respect that here.
      $this->config->replace($this->processor->export());
      $this->processor->extend($this->loader->load($this->webRoot . "/sites/{$this->site}/blt.yml"));
      $this->processor->extend($this->loader->load($this->webRoot . "/sites/{$this->site}/{$this->environment}.blt.yml"));
    }

    return $this;
  }

  /**
   * Determine env.
   *
   * @return string|bool
   *   Env.
   *
   * @throws \ReflectionException
   */
  public function determineEnvironment(): string {
    if (EnvironmentDetector::isCiEnv()) {
      return 'ci';
    }
    return 'local';
  }

  /**
   * Process config.
   *
   * @return $this
   *   Config.
   */
  public function processConfigFiles(): ConfigInitializer {
    $this->config->replace($this->processor->export());
    return $this;
  }

}
