<?php

/**
 * @file
 * Cleanup local repos.
 */

$json_file = file_get_contents($argv[1]);
$jsonData = json_decode($json_file);
foreach ($jsonData->repositories as $name => &$repository) {
  if (str_starts_with($name, "drupal/acquia_cms")) {
    echo $name . PHP_EOL;
  }
}
