<?php

declare(strict_types=1);

namespace Drupal\tests\acquia_starterkit_core\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\acquia_starterkit_core\EntityOperations\FieldBundleUpdater;

/**
 * Tests the FieldBundleUpdater class.
 *
 * @coversDefaultClass \Drupal\acquia_starterkit_core\EntityOperations\FieldBundleUpdater
 * @group acquia_starterkit_core
 */
class FieldBundleUpdaterTest extends UnitTestCase {

  /**
   * The FieldBundleUpdater instance.
   *
   * @var \Drupal\acquia_starterkit_core\EntityOperations\FieldBundleUpdater
   */
  protected FieldBundleUpdater $fieldBundleUpdater;

  /**
   * Mock for the FieldConfigInterface.
   *
   * @var \Drupal\Core\Field\FieldConfigInterface
   */
  protected $entity;

  /**
   * Mock for the EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');

    $container->method('get')
      ->with('entity_type.manager')
      ->willReturn($this->entityTypeManager);

    $this->fieldBundleUpdater = FieldBundleUpdater::create($container);
    $this->entity = $this->createMock('Drupal\Core\Field\FieldConfigInterface');
  }

  /**
   * Tests the filterBundles method for invalid handlers.
   *
   * @param array $settings
   *   Mock settings for the entity.
   * @param string $expectedMessage
   *   The expected exception message.
   *
   * @dataProvider invalidHandlerProvider
   *
   * @covers ::filterBundles
   */
  public function testFilterBundlesInvalidHandlers(array $settings, string $expectedMessage): void {
    $method = $this->getReflectionMethod('filterBundles');
    $this->entity->method('get')->with('settings')->willReturn($settings);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage($expectedMessage);

    $method->invoke($this->fieldBundleUpdater, $this->entity, ['bundle1', 'bundle2']);
  }

  /**
   * Tests the filterBundles method with valid data.
   *
   * @covers ::filterBundles
   */
  public function testFilterBundlesValid(): void {
    $this->entity->method('get')->with('settings')->willReturn(['handler' => 'default:taxonomy_term']);

    $entityType = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');
    $entityType->method('getBundleEntityType')->willReturn('taxonomy_vocabulary');

    $storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->exactly(2))
      ->method('load')
      ->willReturnOnConsecutiveCalls(TRUE, NULL);

    $this->entityTypeManager->method('getDefinition')
      ->with('taxonomy_term')
      ->willReturn($entityType);

    $this->entityTypeManager->method('getStorage')
      ->with('taxonomy_vocabulary')
      ->willReturn($storage);

    $method = $this->getReflectionMethod('filterBundles');
    $target_bundles = ['bundle1', 'bundle2'];

    $result = $method->invoke($this->fieldBundleUpdater, $this->entity, $target_bundles);
    $this->assertSame(['bundle1'], $result);
  }

  /**
   * Tests the shouldSkipOperations method.
   *
   * @param bool $isSyncing
   *   Determine if configuration is syncing.
   * @param array $target_bundles
   *   An array of bundle names.
   * @param bool $expected
   *   Expected result.
   *
   * @dataProvider provideShouldSkipOperationsCases
   *
   * @covers ::shouldSkipOperations
   */
  public function testShouldSkipOperations(bool $isSyncing, array $target_bundles, bool $expected): void {
    $this->entity->method('getThirdPartySettings')
      ->with('acquia_starterkit_core')
      ->willReturn($target_bundles);

    $this->entity->method('isSyncing')->willReturn($isSyncing);

    $this->assertSame($expected, $this->fieldBundleUpdater->shouldSkipOperations($this->entity));
  }

  /**
   * Helper method to access protected methods via reflection.
   *
   * @param string $methodName
   *   The protected method name.
   *
   * @throws \ReflectionException
   */
  protected function getReflectionMethod(string $methodName): \ReflectionMethod {
    return new \ReflectionMethod(FieldBundleUpdater::class, $methodName);
  }

  /**
   * Provides test cases for invalid handlers.
   */
  public static function invalidHandlerProvider(): array {
    return [
      'Missing handler' => [[], "The 'handler' setting is not defined in the entity configuration."],
      'Invalid handler' => [['handler' => 'invalid:handler'], "Unsupported handler: 'invalid:handler'. The handler must start with 'default:'."],
    ];
  }

  /**
   * Provides test cases for shouldSkipOperations().
   */
  public static function provideShouldSkipOperationsCases(): array {
    return [
      'Entity is syncing' => [TRUE, ['target_bundles' => ['add' => ['tags']]], TRUE],
      'Entity not syncing, bundles empty' => [FALSE, [], TRUE],
      'Entity not syncing, bundles present' => [FALSE, ['target_bundles' => ['add' => ['tags']]], FALSE],
    ];
  }

}
