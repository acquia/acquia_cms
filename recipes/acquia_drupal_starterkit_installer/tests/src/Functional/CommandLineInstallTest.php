<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_drupal_starterkit_installer\Functional;

use Drupal\Core\Test\TestSetupTrait;
use Drush\TestTraits\DrushTestTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * @group acquia_drupal_starterkit_installer
 * @requires extension pdo_sqlite
 */
class CommandLineInstallTest extends TestCase {

  use DrushTestTrait;
  use TestSetupTrait;

  /**
   * The full path to the test site directory.
   *
   * @var string
   */
  private string $sitePath;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->root = dirname((new \ReflectionClass('Drupal'))->getFileName(), 3);
    $this->prepareDatabasePrefix();
    $this->sitePath = $this->root . '/' . $this->siteDirectory;

    mkdir($this->sitePath, recursive: TRUE);

    // This is needed for Drush to work properly.
    if (!defined('DRUPAL_TEST_IN_CHILD_SITE')) {
      define('DRUPAL_TEST_IN_CHILD_SITE', FALSE);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // To help with forensic debugging, only delete the site directory if the
    // test passed.
    if ($this->hasFailed() === FALSE) {
      $file_system = new Filesystem();
      $file_system->chmod($this->sitePath, 0755);
      $file_system->remove($this->sitePath);
    }
    parent::tearDown();
  }

  private function assertPostInstallState(): void {
    // Confirm that there's no install profile.
    $this->drush('core:status', options: ['field' => 'install-profile'], cd: $this->root);
    $this->assertEmpty($this->getOutput());

    // Confirm that non-core extensions are installed.
    $options = [
      'format' => 'json',
      'no-core' => TRUE,
      'status' => 'enabled',
    ];
    $this->drush('pm:list', options: $options, cd: $this->root);
    $this->assertNotEmpty($this->getOutputFromJSON());

    // Confirm that Gin is the admin theme.
    $this->drush('config:get', ['system.theme', 'admin'], ['format' => 'json'], cd: $this->root);
    $this->assertSame('gin', $this->getOutputFromJSON('system.theme:admin'));
  }

  public function testDrushSiteInstall(): void {
    $options = [
      'yes' => TRUE,
      'sites-subdir' => substr($this->siteDirectory, 6),
      'db-url' => "sqlite://$this->siteDirectory/files/.sqlite",
    ];
    $this->drush('site:install', options: $options, cd: $this->root);

    $this->assertPostInstallState();
  }

  public function testCoreInstallCommand(): void {
    $command = [
      PHP_BINDIR . '/php',
      'core/scripts/drupal',
      'install',
      'acquia_drupal_starterkit_installer',
    ];
    $process = new Process($command, $this->root, [
      'DRUPAL_DEV_SITE_PATH' => $this->siteDirectory,
    ]);
    $process->mustRun();
    $this->assertStringContainsString('Congratulations, you installed Acquia Drupal Starterkit!', $process->getErrorOutput());

    // The core install command write-protects the site directory, which
    // interferes with $this->drush().
    chmod($this->sitePath, 0755);

    $this->assertPostInstallState();
  }

}
