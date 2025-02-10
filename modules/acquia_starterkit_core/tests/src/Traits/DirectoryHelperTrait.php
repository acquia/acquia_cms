<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_starterkit_core\Traits;

use PHPUnit\Framework\Assert;

/**
 * Trait for helper methods to manage directories.
 */
trait DirectoryHelperTrait {

  /**
   * Make a clone of directory at given path.
   *
   * @param string $source
   *   Given directory path to clone.
   * @param string $destination
   *   The destination directory path.
   */
  private function cloneDirectory(string $source, string $destination): string {
    $this->copyDirectory($source, $destination);
    Assert::assertDirectoryExists($destination);
    return $destination;
  }

  /**
   * Copy files and directories from source path to destination path.
   *
   * @param string $source
   *   Given source directory path.
   * @param string $destination
   *   Given destination directory path.
   */
  private function copyDirectory(string $source, string $destination) {
    if (!is_dir($destination)) {
      mkdir($destination, 0755, TRUE);
    }
    foreach (scandir($source) as $file) {
      if ($file === '.' || $file === '..') {
        continue;
      }

      $srcPath = rtrim($source, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
      $destPath = rtrim($destination, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;

      if (is_dir($srcPath)) {
        $this->copyDirectory($srcPath, $destPath);
      }
      else {
        copy($srcPath, $destPath);
      }
    }
  }

}
