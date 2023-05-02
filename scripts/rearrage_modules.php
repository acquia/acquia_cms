<?php

/**
 * @file
 * Create symlink for contributed modules.
 */

if (!isset($argv[1])) {
  log_message("Please provide module name. Ex: php ./scripts/rearrage_modules.php pathauto", "error");
  exit(1);
}
$module = $argv[1];

$destinationRootDir = getenv("ORCA_FIXTURE_DIR") ?: dirname(__DIR__);
$sourceRootDir = getenv("ORCA_SUT_DIR") ?: dirname(__DIR__);
$contribModulesDirectory = $sourceRootDir . "/modules";
if (!file_exists($contribModulesDirectory)) {
  mkdir($contribModulesDirectory, 0755, TRUE);
}
$moduleDir = $destinationRootDir . "/docroot/modules/contrib/" . $module;
if (file_exists($moduleDir)) {
  symlink($moduleDir, $contribModulesDirectory . "/" . $module);
}
else {
  log_message("The contributed module: `$module` doesn't exist at path: `$moduleDir`.", "error");
  exit(1);
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
