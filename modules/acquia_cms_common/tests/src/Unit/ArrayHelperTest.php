<?php

namespace Drupal\acquia_cms_common\Utility;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the ArrayHelper class.
 */
class ArrayHelperTest extends UnitTestCase {

  /**
   * Tests the sort method of ArrayHelper class.
   *
   * @dataProvider sortArrayDataProvider
   */
  public function testSort(array $actual, array $expected): void {
    $this->assertSame($expected, ArrayHelper::sort($actual));
  }

  /**
   * Tests the isSame method of ArrayHelper class.
   *
   * @param array $actual
   *   From array to compare with.
   * @param array $expected
   *   To array to compare with.
   * @param string $condition
   *   Condition check. ex: assertTrue or assertFalse.
   *
   * @dataProvider sameArrayDataProvider
   */
  public function testIsSame(array $actual, array $expected, string $condition): void {
    $this->$condition(ArrayHelper::isSame($actual, $expected));
  }

  /**
   * Provides the dataProvider for sort method of ArrayHelper class.
   */
  public static function sortArrayDataProvider(): array {
    return [
      [
        [
          "environment" => "dev",
          "modules" => [
            "acquia_purge" => [
              "version" => "dev",
              "status" => "enabled",
            ],
            "purge" => [
              "status" => "enabled",
              "version" => "stable",
            ],
            "acquia_connector" => [
              "version" => "dev",
              "status" => "enabled",
            ],
          ],
          "application_id" => "some_application_id",
          "profile" => "some-profile",
          "install_time" => 500,
        ],
        [
          "application_id" => "some_application_id",
          "environment" => "dev",
          "install_time" => 500,
          "modules" => [
            "acquia_connector" => [
              "status" => "enabled",
              "version" => "dev",
            ],
            "acquia_purge" => [
              "status" => "enabled",
              "version" => "dev",
            ],
            "purge" => [
              "status" => "enabled",
              "version" => "stable",
            ],
          ],
          "profile" => "some-profile",
        ],
      ],
      [
        [
          "environment" => "stable",
          "application" => "123",
          "profile" => "some-profile",
        ],
        [
          "application" => "123",
          "environment" => "stable",
          "profile" => "some-profile",
        ],
      ],
    ];
  }

  /**
   * Provides the dataProvider for isSame method of ArrayHelper class.
   */
  public static function sameArrayDataProvider(): array {
    return [
      [
        [
          "application_id" => "some-id",
          "profile" => "some-profile",
          "environment" => "dev",
        ],
        [
          "application_id" => "some-id",
          "environment" => "dev",
          "profile" => "some-profile",
        ],
        "assertTrue",
      ],
      [
        [
          "application_id" => "some-id",
          "profile" => "some-profile",
          "environment" => "dev",
        ],
        [
          "application_id" => "another-id",
          "environment" => "dev",
          "profile" => "some-profile",
        ],
        "assertFalse",
      ],
      [
        [
          "profile" => "some-another-profile",
          "environment" => "dev",
        ],
        [
          "environment" => "dev",
          "profile" => "some-profile",
        ],
        "assertFalse",
      ],
    ];
  }

}
