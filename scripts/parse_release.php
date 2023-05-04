<?php

/**
 * @file
 * Returns the latest released tag for given module.
 */

$shortopts = "m:";
// Required value.
$shortopts = "a";

$longopts = [
  "module:",
  "all",
];
$options = getopt($shortopts, $longopts);
$module = $options['module'] ?? "";
$all = isset($options['a']);
if (!$module && !$all) {
  log_message("The --module option is required option or use -a to get release information for all modules.", "error");
  exit;
}
$modules = [
  "acquia_cms_article" => "1.x",
  "acquia_cms_audio" => "1.x",
  "acquia_cms_common" => "3.x",
  "acquia_cms_component" => "1.x",
  "acquia_cms_dam" => "1.x",
  "acquia_cms_document" => "1.x",
  "acquia_cms_event" => "1.x",
  "acquia_cms_headless" => "1.x",
  "acquia_cms_image" => "1.x",
  "acquia_cms_page" => "1.x",
  "acquia_cms_person" => "1.x",
  "acquia_cms_place" => "1.x",
  "acquia_cms_search" => "1.x",
  "acquia_cms_site_studio" => "1.x",
  "acquia_cms_starter" => "1.x",
  "acquia_cms_toolbar" => "1.x",
  "acquia_cms_tour" => "2.x",
  "acquia_cms_video" => "1.x",
];


if (!$all) {
  $modules = [
    $module => $modules[$module],
  ];
}
foreach ($modules as $module => $dev) {
  $xmlFile = file_get_contents("https://updates.drupal.org/release-history/$module/current");
  // Convert xml string into an object.
  $new = simplexml_load_string($xmlFile);

  // Convert into json.
  $con = json_encode($new);

  // Convert into associative array.
  $moduleArray = json_decode($con, TRUE);
  if (!isset($moduleArray["title"]) && isset($moduleArray[0])) {
    log_message($moduleArray[0], "error");
    exit;
  }

  $latestRelease = $moduleArray['releases']['release'][0] ?? [];
  echo isset($latestRelease['tag']) ? "module: $module tag: " . $latestRelease['tag'] . " dev: " . $dev . PHP_EOL : "";
}

/**
 * Formats the log to display on terminal.
 *
 * @param string $message
 *   The message to log.
 * @param string $type
 *   The log type.
 */
function format_log(string $message, string $type = 'info'): string {
  return match ($type) {
    'error' => "\033[31m$message \033[0m\n",
    'success' => "\033[32m$message \033[0m\n",
    'warning' => "\033[33m$message \033[0m\n",
    default => "\033[36m$message \033[0m\n",
  };
}

/**
 * Logs the message.
 *
 * @param string $message
 *   The message to log.
 * @param string $type
 *   The log type.
 */
function log_message(string $message, string $type = "info"): void {
  $message = format_log($message, $type) . PHP_EOL;
  print($message);
  flush();
  sleep(1);
}
