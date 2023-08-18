<?php

namespace Drupal\acquia_cms_common\Utility;

/**
 * Provides helper functions for array.
 */
class ArrayHelper {

  /**
   * Sort the given array.
   *
   * @param array $array
   *   An input array to sort.
   */
  public static function sort(array $array): array {
    ksort($array);
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $array[$key] = self::sort($value);
      }
    }
    return $array;
  }

  /**
   * Checks if both array are same.
   *
   * @param array $from
   *   From array to compare with.
   * @param array $to
   *   An array to compare with.
   */
  public static function isSame(array $from, array $to): bool {
    return self::sort($from) === self::sort($to);
  }

}
