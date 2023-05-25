<?php

namespace Drupal\Tests\sitestudio_config_management\Kernel;

use Consolidation\SiteAlias\SiteAlias;
use Consolidation\SiteAlias\SiteAliasManager;
use Consolidation\SiteProcess\ProcessBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\sitestudio_config_management\Traits\DrushCommandTrait;
use Drush\Drush;
use Drush\SiteAlias\ProcessManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

class DrushCommandTraitTest extends KernelTestBase {

  use DrushCommandTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ["sitestudio_config_management"];

  /**
   * The mock object for logger service.
   *
   * @var mixed|\PHPUnit\Framework\MockObject\MockObject|LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->logger = $this->createMock('\Psr\Log\LoggerInterface');
    $this->logger->expects($this->any())
      ->method('error')
      ->willReturnCallback(
        function ($message) {
          $this->assertStringContainsString('Command something was not found', $message);
        });
    $this->logger->expects($this->any())
      ->method('notice')
      ->willReturnCallback(
        function ($message) {
          $this->assertStringStartsWith("Running command: > ", $message);
        });
    parent::setUp();
    $this->configureDrushProcess();
  }

  public function testInitialize() {
    $this->assertFalse($this->initialized);
    $this->initialize();
    $this->assertTrue($this->initialized);
  }

  /**
   * Tests the addCommand() method.
   */
  public function testAddCommand(): void {
    $this->addCommand("core:status");
    $this->addCommand("core:status");
    $this->assertCount(2, $this->commands);
  }

  /**
   * Tests execute() method.
   *
   * @dataProvider commandsDataProvider
   */
  public function testExecute($command, $status, $count): void {
    $this->addCommand($command);
    $this->assertSame($this->execute(), $status, "Executed command status");
    $this->assertCount($count, $this->commands, "Number of remaining commands to execute.");
  }

  /**
   * {@inheritdoc}
   */
  protected function logger(): LoggerInterface {
    return $this->logger;
  }

  /**
   * Configure & Initialize the Drush Process.
   */
  private function configureDrushProcess(): void {
    $processManager = $this->prophesize(ProcessManager::class);
    $output = $this->prophesize(OutputInterface::class);
    $commands = $this->commandsDataProvider();
    $commands = array_map(fn($subArray) => $subArray[0], $commands);
    foreach ($commands as $command) {
      $processBase = new ProcessBase(["./vendor/bin/drush", $command, "--root=" . DRUPAL_ROOT], Path::makeAbsolute("../", DRUPAL_ROOT));
      $processBase->setRealtimeOutput($output->reveal());
      $processManager->drush(new SiteAlias(), $command)->willReturn($processBase); /* @phpstan-ignore-line */
    }
    $siteAliasManager = $this->prophesize(SiteAliasManager::class);
    $siteAliasManager->getSelf()->willReturn(new SiteAlias()); /* @phpstan-ignore-line */
    $processManager = $processManager->reveal();
    $siteAliasManager = $siteAliasManager->reveal();
    $container = new ContainerBuilder();
    $container->set("process.manager", $processManager);
    $container->set("site.alias.manager", $siteAliasManager);
    Drush::setContainer($container);
  }

  /**
   * Returns an array of commands data provider.
   */
  public function commandsDataProvider(): array {
    return [
      ["core:status", TRUE, 0],
      ["something", FALSE, 1],
    ];
  }

}
