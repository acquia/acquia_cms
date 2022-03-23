<?php

namespace Drupal\Tests\acquia_cms_tour\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests that acquia cms tour manager is working.
 *
 * @group acquia_cms_tour
 */
class AcquiaCmsTourManagerTest extends UnitTestCase {

  /**
   * Mock tour manager to use for the test.
   *
   * @var array
   */
  protected $mockTourManager = [
    'module_a' => [
      'id' => 'module_a',
      'label' => 'Module A',
      'weight' => 1,
    ],
    'module_b' => [
      'id' => 'module_b',
      'label' => 'Module B',
      'weight' => 3,
    ],
    'module_c' => [
      'id' => 'module_c',
      'label' => 'Module C',
      'weight' => 2,
    ],
  ];

  /**
   * Test TourManager so that it has defined structure, and it's intact.
   */
  public function testTourManager() {
    $options = $this->getTourManager()->getTourManagerPlugin();
    $this->assertEquals([
      'module_a' => [
        'id' => 'module_a',
        'label' => 'Module A',
        'weight' => 1,
      ],
      'module_b' => [
        'id' => 'module_b',
        'label' => 'Module B',
        'weight' => 3,
      ],
      'module_c' => [
        'id' => 'module_c',
        'label' => 'Module C',
        'weight' => 2,
      ],
    ], $options);
  }

  /**
   * Get a mock AcquiaCmsTourManager.
   */
  protected function getTourManager() {
    $definitions = $this->mockTourManager;
    $manager = $this->getMockBuilder('Drupal\acquia_cms_tour\AcquiaCmsTourManager')
      ->disableOriginalConstructor()
      ->onlyMethods(['getDefinitions', 'getDefinition', 'createInstance'])
      ->getMock();
    $manager
      ->method('getDefinitions')
      ->willReturn($definitions);
    $manager
      ->method('getDefinition')
      ->willReturnCallback(function ($value) use ($definitions) {
        return $definitions[$value];
      });
    $manager
      ->method('createInstance')
      ->willReturnCallback(function ($name) {
        return (object) ['id' => $name];
      });
    return $manager;
  }

}
