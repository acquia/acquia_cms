<?php

namespace Drupal\acquia_cms_common\Traits;

/**
 * Provides helpers to generate random password.
 */
trait PasswordGeneratorTrait {

  /**
   * Generates the password based upon certain required conditions.
   *
   * @param int $length
   *   Parameter that accepts the length of the password.
   *
   * @return string
   *   Returns the shuffled string password.
   */
  public static function generateRandomPassword(int $length = 12): string {
    // Define the character libraries.
    $sets = [];
    $sets[] = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $sets[] = 'abcdefghjkmnpqrstuvwxyz';
    $sets[] = '0123456789';
    $sets[] = '~!@#$%^&*(){}[],./?';
    $password = '';

    // Enforce the minimum length  of the password to be 12.
    if ($length < 12) {
      $length = 12;
    }

    // Append a character from each set - gets first 4 characters.
    foreach ($sets as $set) {
      $password .= $set[array_rand(str_split($set))];
    }

    // Use all characters to fill up to $length.
    while (strlen($password) < $length) {
      // Get a random set.
      $randomSet = $sets[array_rand($sets)];
      // Add a random char from the random set.
      $password .= $randomSet[array_rand(str_split($randomSet))];
    }

    // Shuffle the password string before returning.
    return str_shuffle($password);
  }

}
